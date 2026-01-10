<?php
/**
 * Trico Cloudflare Integration
 * Cloudflare Pages API for deployment
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_Cloudflare {
    
    private $api_token;
    private $account_id;
    private $base_url = 'https://api.cloudflare.com/client/v4';
    
    public function __construct() {
        $this->api_token = TRICO_CF_API_TOKEN;
        $this->account_id = TRICO_CF_ACCOUNT_ID;
    }
    
    /**
     * Check if Cloudflare is configured
     */
    public function is_configured() {
        return !empty($this->api_token) && !empty($this->account_id);
    }
    
    /**
     * Make API request to Cloudflare
     */
    private function request($endpoint, $method = 'GET', $data = null) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', __('Cloudflare API not configured', 'trico-ai'));
        }
        
        $url = $this->base_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 60
        );
        
        if ($data !== null && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            trico()->core->log('CF API Error: ' . $response->get_error_message(), 'error');
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($code >= 400) {
            $error_msg = isset($body['errors'][0]['message']) 
                ? $body['errors'][0]['message'] 
                : 'Unknown API error';
            
            trico()->core->log("CF API Error ({$code}): {$error_msg}", 'error');
            
            return new WP_Error('cf_api_error', $error_msg, array('code' => $code));
        }
        
        return $body;
    }
    
    /**
     * Upload file for direct upload deployment
     */
    private function upload_file($upload_url, $file_path, $file_name) {
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', 'File not found: ' . $file_path);
        }
        
        $boundary = wp_generate_password(24, false);
        $content_type = mime_content_type($file_path);
        $file_content = file_get_contents($file_path);
        
        $body = "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$file_name}\"\r\n";
        $body .= "Content-Type: {$content_type}\r\n\r\n";
        $body .= $file_content . "\r\n";
        $body .= "--{$boundary}--\r\n";
        
        $response = wp_remote_post($upload_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'multipart/form-data; boundary=' . $boundary
            ),
            'body' => $body,
            'timeout' => 120
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
    
    // ==========================================
    // PAGES PROJECTS
    // ==========================================
    
    /**
     * List all Pages projects
     */
    public function list_projects() {
        return $this->request("/accounts/{$this->account_id}/pages/projects");
    }
    
    /**
     * Get a Pages project
     */
    public function get_project($project_name) {
        return $this->request("/accounts/{$this->account_id}/pages/projects/{$project_name}");
    }
    
    /**
     * Create a new Pages project
     */
    public function create_project($name, $production_branch = 'main') {
        $data = array(
            'name' => sanitize_title($name),
            'production_branch' => $production_branch
        );
        
        return $this->request(
            "/accounts/{$this->account_id}/pages/projects",
            'POST',
            $data
        );
    }
    
    /**
     * Delete a Pages project
     */
    public function delete_project($project_name) {
        return $this->request(
            "/accounts/{$this->account_id}/pages/projects/{$project_name}",
            'DELETE'
        );
    }
    
    // ==========================================
    // DEPLOYMENTS
    // ==========================================
    
    /**
     * Create deployment with direct upload
     */
    public function create_deployment($project_name, $files_directory) {
        // Step 1: Get upload token
        $upload_response = $this->request(
            "/accounts/{$this->account_id}/pages/projects/{$project_name}/deployments",
            'POST',
            array()
        );
        
        if (is_wp_error($upload_response)) {
            return $upload_response;
        }
        
        if (!isset($upload_response['result']['id'])) {
            return new WP_Error('deployment_failed', 'Failed to create deployment');
        }
        
        $deployment_id = $upload_response['result']['id'];
        
        // Step 2: Upload files
        $files = $this->get_directory_files($files_directory);
        
        foreach ($files as $file) {
            $relative_path = str_replace($files_directory, '', $file);
            $relative_path = ltrim($relative_path, '/\\');
            
            // Upload each file
            $file_hash = hash_file('sha256', $file);
            
            $upload_result = $this->request(
                "/accounts/{$this->account_id}/pages/projects/{$project_name}/deployments/{$deployment_id}/files/{$relative_path}",
                'PUT',
                array(
                    'content' => base64_encode(file_get_contents($file)),
                    'hash' => $file_hash
                )
            );
            
            if (is_wp_error($upload_result)) {
                trico()->core->log("Failed to upload {$relative_path}: " . $upload_result->get_error_message(), 'error');
            }
        }
        
        // Step 3: Finalize deployment
        $finalize = $this->request(
            "/accounts/{$this->account_id}/pages/projects/{$project_name}/deployments/{$deployment_id}",
            'PATCH',
            array('status' => 'active')
        );
        
        if (is_wp_error($finalize)) {
            return $finalize;
        }
        
        return $finalize;
    }
    
    /**
     * Simple deployment using form upload
     */
    public function deploy_simple($project_name, $files_directory) {
        // Create zip of files
        $zip_path = $this->create_deployment_zip($files_directory);
        
        if (is_wp_error($zip_path)) {
            return $zip_path;
        }
        
        // Create deployment
        $boundary = wp_generate_password(24, false);
        $zip_content = file_get_contents($zip_path);
        
        $body = "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"manifest\"\r\n";
        $body .= "Content-Type: application/json\r\n\r\n";
        $body .= json_encode(array('branch' => 'main')) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"_worker.bundle\"; filename=\"files.zip\"\r\n";
        $body .= "Content-Type: application/zip\r\n\r\n";
        $body .= $zip_content . "\r\n";
        $body .= "--{$boundary}--\r\n";
        
        $response = wp_remote_post(
            "{$this->base_url}/accounts/{$this->account_id}/pages/projects/{$project_name}/deployments",
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_token,
                    'Content-Type' => 'multipart/form-data; boundary=' . $boundary
                ),
                'body' => $body,
                'timeout' => 300
            )
        );
        
        // Cleanup
        @unlink($zip_path);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['result'])) {
            return new WP_Error('deployment_failed', $body['errors'][0]['message'] ?? 'Deployment failed');
        }
        
        return $body;
    }
    
    /**
     * Create zip file for deployment
     */
    private function create_deployment_zip($directory) {
        if (!class_exists('ZipArchive')) {
            return new WP_Error('zip_not_available', 'ZipArchive extension not available');
        }
        
        $zip_path = sys_get_temp_dir() . '/trico-deploy-' . uniqid() . '.zip';
        
        $zip = new ZipArchive();
        
        if ($zip->open($zip_path, ZipArchive::CREATE) !== true) {
            return new WP_Error('zip_create_failed', 'Failed to create zip file');
        }
        
        $files = $this->get_directory_files($directory);
        
        foreach ($files as $file) {
            $relative_path = str_replace($directory, '', $file);
            $relative_path = ltrim($relative_path, '/\\');
            
            $zip->addFile($file, $relative_path);
        }
        
        $zip->close();
        
        return $zip_path;
    }
    
    /**
     * Get all files in directory recursively
     */
    private function get_directory_files($directory) {
        $files = array();
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * List deployments for a project
     */
    public function list_deployments($project_name, $limit = 10) {
        return $this->request(
            "/accounts/{$this->account_id}/pages/projects/{$project_name}/deployments?per_page={$limit}"
        );
    }
    
    /**
     * Get deployment details
     */
    public function get_deployment($project_name, $deployment_id) {
        return $this->request(
            "/accounts/{$this->account_id}/pages/projects/{$project_name}/deployments/{$deployment_id}"
        );
    }
    
    /**
     * Rollback to previous deployment
     */
    public function rollback($project_name, $deployment_id) {
        return $this->request(
            "/accounts/{$this->account_id}/pages/projects/{$project_name}/deployments/{$deployment_id}/rollback",
            'POST'
        );
    }
    
    // ==========================================
    // DOMAINS
    // ==========================================
    
    /**
     * Add custom domain to project
     */
    public function add_domain($project_name, $domain) {
        return $this->request(
            "/accounts/{$this->account_id}/pages/projects/{$project_name}/domains",
            'POST',
            array('name' => $domain)
        );
    }
    
    /**
     * List domains for a project
     */
    public function list_domains($project_name) {
        return $this->request(
            "/accounts/{$this->account_id}/pages/projects/{$project_name}/domains"
        );
    }
    
    /**
     * Delete domain from project
     */
    public function delete_domain($project_name, $domain) {
        return $this->request(
            "/accounts/{$this->account_id}/pages/projects/{$project_name}/domains/{$domain}",
            'DELETE'
        );
    }
    
    // ==========================================
    // ANALYTICS
    // ==========================================
    
    /**
     * Get Web Analytics data
     */
    public function get_analytics($site_tag, $since = '-7d', $until = 'now') {
        // Web Analytics uses GraphQL
        $query = <<<GRAPHQL
{
  viewer {
    accounts(filter: {accountTag: "{$this->account_id}"}) {
      rumPageloadEventsAdaptiveGroups(
        filter: {
          AND: [
            {siteTag: "{$site_tag}"},
            {datetime_geq: "{$since}"},
            {datetime_leq: "{$until}"}
          ]
        }
        limit: 10000
        orderBy: [datetime_ASC]
      ) {
        count
        dimensions {
          datetime
          countryName
          deviceType
          browserName
          path
        }
        avg {
          sampleInterval
        }
        sum {
          visits
          pageViews
        }
      }
    }
  }
}
GRAPHQL;
        
        $response = wp_remote_post('https://api.cloudflare.com/client/v4/graphql', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array('query' => $query)),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
    
    /**
     * Get Pages project analytics (simpler)
     */
    public function get_pages_analytics($project_name, $days = 7) {
        $since = date('Y-m-d\TH:i:s\Z', strtotime("-{$days} days"));
        $until = date('Y-m-d\TH:i:s\Z');
        
        return $this->request(
            "/accounts/{$this->account_id}/pages/projects/{$project_name}/metrics?" .
            http_build_query(array(
                'since' => $since,
                'until' => $until
            ))
        );
    }
    
    // ==========================================
    // WEB ANALYTICS (Separate Product)
    // ==========================================
    
    /**
     * Create Web Analytics site
     */
    public function create_analytics_site($domain, $auto_install = true) {
        return $this->request(
            "/accounts/{$this->account_id}/rum/site_info",
            'POST',
            array(
                'host' => $domain,
                'auto_install' => $auto_install
            )
        );
    }
    
    /**
     * Get Web Analytics site info
     */
    public function get_analytics_site($site_tag) {
        return $this->request(
            "/accounts/{$this->account_id}/rum/site_info/{$site_tag}"
        );
    }
    
    /**
     * List all Web Analytics sites
     */
    public function list_analytics_sites() {
        return $this->request(
            "/accounts/{$this->account_id}/rum/site_info/list"
        );
    }
    
    /**
     * Get analytics summary
     */
    public function get_analytics_summary($site_tag, $days = 7) {
        $since = date('Y-m-d', strtotime("-{$days} days"));
        $until = date('Y-m-d');
        
        $query = <<<GRAPHQL
{
  viewer {
    accounts(filter: {accountTag: "{$this->account_id}"}) {
      rumPageloadEventsAdaptiveGroups(
        filter: {
          AND: [
            {siteTag: "{$site_tag}"},
            {date_geq: "{$since}"},
            {date_leq: "{$until}"}
          ]
        }
        limit: 1000
      ) {
        sum {
          visits
          pageViews
        }
        dimensions {
          date
        }
      }
      
      topPaths: rumPageloadEventsAdaptiveGroups(
        filter: {
          AND: [
            {siteTag: "{$site_tag}"},
            {date_geq: "{$since}"},
            {date_leq: "{$until}"}
          ]
        }
        limit: 10
        orderBy: [sum_visits_DESC]
      ) {
        sum {
          visits
          pageViews
        }
        dimensions {
          path
        }
      }
      
      topCountries: rumPageloadEventsAdaptiveGroups(
        filter: {
          AND: [
            {siteTag: "{$site_tag}"},
            {date_geq: "{$since}"},
            {date_leq: "{$until}"}
          ]
        }
        limit: 10
        orderBy: [sum_visits_DESC]
      ) {
        sum {
          visits
        }
        dimensions {
          countryName
        }
      }
      
      devices: rumPageloadEventsAdaptiveGroups(
        filter: {
          AND: [
            {siteTag: "{$site_tag}"},
            {date_geq: "{$since}"},
            {date_leq: "{$until}"}
          ]
        }
        limit: 5
        orderBy: [sum_visits_DESC]
      ) {
        sum {
          visits
        }
        dimensions {
          deviceType
        }
      }
    }
  }
}
GRAPHQL;
        
        $response = wp_remote_post('https://api.cloudflare.com/client/v4/graphql', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array('query' => $query)),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        return $this->parse_analytics_response($data);
    }
    
    /**
     * Parse analytics response into usable format
     */
    private function parse_analytics_response($data) {
        if (!isset($data['data']['viewer']['accounts'][0])) {
            return array(
                'total_visits' => 0,
                'total_pageviews' => 0,
                'daily' => array(),
                'top_pages' => array(),
                'top_countries' => array(),
                'devices' => array()
            );
        }
        
        $account = $data['data']['viewer']['accounts'][0];
        
        // Calculate totals
        $total_visits = 0;
        $total_pageviews = 0;
        $daily = array();
        
        if (isset($account['rumPageloadEventsAdaptiveGroups'])) {
            foreach ($account['rumPageloadEventsAdaptiveGroups'] as $group) {
                $total_visits += $group['sum']['visits'] ?? 0;
                $total_pageviews += $group['sum']['pageViews'] ?? 0;
                
                if (isset($group['dimensions']['date'])) {
                    $daily[$group['dimensions']['date']] = array(
                        'visits' => $group['sum']['visits'] ?? 0,
                        'pageviews' => $group['sum']['pageViews'] ?? 0
                    );
                }
            }
        }
        
        // Top pages
        $top_pages = array();
        if (isset($account['topPaths'])) {
            foreach ($account['topPaths'] as $path) {
                $top_pages[] = array(
                    'path' => $path['dimensions']['path'] ?? '/',
                    'visits' => $path['sum']['visits'] ?? 0,
                    'pageviews' => $path['sum']['pageViews'] ?? 0
                );
            }
        }
        
        // Top countries
        $top_countries = array();
        if (isset($account['topCountries'])) {
            foreach ($account['topCountries'] as $country) {
                $top_countries[] = array(
                    'country' => $country['dimensions']['countryName'] ?? 'Unknown',
                    'visits' => $country['sum']['visits'] ?? 0
                );
            }
        }
        
        // Devices
        $devices = array();
        if (isset($account['devices'])) {
            foreach ($account['devices'] as $device) {
                $devices[] = array(
                    'type' => $device['dimensions']['deviceType'] ?? 'Unknown',
                    'visits' => $device['sum']['visits'] ?? 0
                );
            }
        }
        
        return array(
            'total_visits' => $total_visits,
            'total_pageviews' => $total_pageviews,
            'daily' => $daily,
            'top_pages' => $top_pages,
            'top_countries' => $top_countries,
            'devices' => $devices
        );
    }
    
    /**
     * Get connection status
     */
    public function get_status() {
        if (!$this->is_configured()) {
            return array(
                'configured' => false,
                'connected' => false,
                'message' => __('Cloudflare API not configured', 'trico-ai')
            );
        }
        
        // Test connection
        $result = $this->request('/user/tokens/verify');
        
        if (is_wp_error($result)) {
            return array(
                'configured' => true,
                'connected' => false,
                'message' => $result->get_error_message()
            );
        }
        
        return array(
            'configured' => true,
            'connected' => isset($result['success']) && $result['success'],
            'account_id' => $this->account_id,
            'message' => __('Connected to Cloudflare', 'trico-ai')
        );
    }
}
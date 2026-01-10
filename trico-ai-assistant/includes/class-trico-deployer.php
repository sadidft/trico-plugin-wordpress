<?php
/**
 * Trico Deployer
 * Orchestrates the deployment process
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_Deployer {
    
    private $cloudflare;
    private $exporter;
    
    public function __construct() {
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-cloudflare.php';
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-exporter.php';
        
        $this->cloudflare = new Trico_Cloudflare();
        $this->exporter = new Trico_Exporter();
    }
    
    /**
     * Deploy a project to Cloudflare Pages
     */
    public function deploy($project_id) {
        $start_time = microtime(true);
        
        // Get project
        $project = trico()->database->get_project($project_id);
        
        if (!$project) {
            return new WP_Error('not_found', __('Project not found', 'trico-ai'));
        }
        
        // Check Cloudflare config
        if (!$this->cloudflare->is_configured()) {
            return new WP_Error('cf_not_configured', __('Cloudflare API not configured', 'trico-ai'));
        }
        
        trico()->core->log("Starting deployment for project {$project_id}", 'info');
        
        // Step 1: Export to static files
        $export_result = $this->exporter->export_project($project_id);
        
        if (is_wp_error($export_result)) {
            return $export_result;
        }
        
        $export_path = $export_result['path'];
        
        // Step 2: Ensure CF Pages project exists
        $cf_project_name = $this->get_cf_project_name($project);
        
        $cf_project = $this->cloudflare->get_project($cf_project_name);
        
        if (is_wp_error($cf_project) || !isset($cf_project['result'])) {
            // Create new project
            $create_result = $this->cloudflare->create_project($cf_project_name);
            
            if (is_wp_error($create_result)) {
                return $create_result;
            }
            
            trico()->core->log("Created CF Pages project: {$cf_project_name}", 'info');
        }
        
        // Step 3: Deploy files
        $deploy_result = $this->cloudflare->deploy_simple($cf_project_name, $export_path);
        
        if (is_wp_error($deploy_result)) {
            return $deploy_result;
        }
        
        $deployment = $deploy_result['result'] ?? array();
        $deployment_url = $deployment['url'] ?? "https://{$cf_project_name}.pages.dev";
        $deployment_id = $deployment['id'] ?? '';
        
        // Step 4: Add custom domain if specified
        if (!empty($project['subdomain'])) {
            $custom_domain = $this->build_custom_domain($project);
            $domain_result = $this->cloudflare->add_domain($cf_project_name, $custom_domain);
            
            if (!is_wp_error($domain_result)) {
                $deployment_url = "https://{$custom_domain}";
                trico()->core->log("Added custom domain: {$custom_domain}", 'info');
            }
        }
        
        // Step 5: Update project in database
        $update_data = array(
            'cf_project_name' => $cf_project_name,
            'cf_deployment_url' => $deployment_url,
            'cf_last_deployment_id' => $deployment_id,
            'last_deployed_at' => current_time('mysql'),
            'status' => 'published'
        );
        
        trico()->database->update_project($project_id, $update_data);
        
        $deploy_time = microtime(true) - $start_time;
        
        trico()->core->log("Deployment complete in {$deploy_time}s: {$deployment_url}", 'info');
        
        return array(
            'success' => true,
            'project_id' => $project_id,
            'cf_project_name' => $cf_project_name,
            'deployment_id' => $deployment_id,
            'url' => $deployment_url,
            'deploy_time' => round($deploy_time, 2)
        );
    }
    
    /**
     * Get Cloudflare project name from Trico project
     */
    private function get_cf_project_name($project) {
        if (!empty($project['cf_project_name'])) {
            return $project['cf_project_name'];
        }
        
        // Generate from slug
        $slug = $project['slug'] ?? sanitize_title($project['name']);
        
        // CF project names: lowercase, alphanumeric, hyphens, max 63 chars
        $name = preg_replace('/[^a-z0-9\-]/', '-', strtolower($slug));
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-');
        
        if (strlen($name) > 63) {
            $name = substr($name, 0, 63);
        }
        
        // Add prefix to avoid conflicts
        $name = 'trico-' . $name;
        
        if (strlen($name) > 63) {
            $name = substr($name, 0, 63);
        }
        
        return $name;
    }
    
    /**
     * Build custom domain from project settings
     */
    private function build_custom_domain($project) {
        $subdomain = $project['subdomain'] ?? $project['slug'];
        $domain = trico()->core->get_site_domain();
        
        // If custom domain is set for the project
        if (!empty($project['custom_domain'])) {
            return $project['custom_domain'];
        }
        
        return "{$subdomain}.{$domain}";
    }
    
    /**
     * Redeploy existing project (update)
     */
    public function redeploy($project_id) {
        return $this->deploy($project_id);
    }
    
    /**
     * Rollback to previous deployment
     */
    public function rollback($project_id, $deployment_id = null) {
        $project = trico()->database->get_project($project_id);
        
        if (!$project || empty($project['cf_project_name'])) {
            return new WP_Error('not_found', __('Project not found or not deployed', 'trico-ai'));
        }
        
        // If no deployment ID specified, get the previous one
        if (is_null($deployment_id)) {
            $deployments = $this->cloudflare->list_deployments($project['cf_project_name'], 2);
            
            if (is_wp_error($deployments) || count($deployments['result'] ?? array()) < 2) {
                return new WP_Error('no_rollback', __('No previous deployment available', 'trico-ai'));
            }
            
            $deployment_id = $deployments['result'][1]['id'];
        }
        
        $result = $this->cloudflare->rollback($project['cf_project_name'], $deployment_id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Update project
        trico()->database->update_project($project_id, array(
            'cf_last_deployment_id' => $deployment_id,
            'last_deployed_at' => current_time('mysql')
        ));
        
        return array(
            'success' => true,
            'deployment_id' => $deployment_id
        );
    }
    
    /**
     * Get deployment history
     */
    public function get_deployment_history($project_id) {
        $project = trico()->database->get_project($project_id);
        
        if (!$project || empty($project['cf_project_name'])) {
            return array();
        }
        
        $deployments = $this->cloudflare->list_deployments($project['cf_project_name'], 10);
        
        if (is_wp_error($deployments)) {
            return array();
        }
        
        $history = array();
        
        foreach ($deployments['result'] ?? array() as $deployment) {
            $history[] = array(
                'id' => $deployment['id'],
                'url' => $deployment['url'],
                'created_at' => $deployment['created_on'],
                'status' => $deployment['latest_stage']['name'] ?? 'unknown',
                'is_current' => $deployment['id'] === ($project['cf_last_deployment_id'] ?? '')
            );
        }
        
        return $history;
    }
    
    /**
     * Delete deployed project from Cloudflare
     */
    public function undeploy($project_id) {
        $project = trico()->database->get_project($project_id);
        
        if (!$project || empty($project['cf_project_name'])) {
            return new WP_Error('not_found', __('Project not deployed', 'trico-ai'));
        }
        
        $result = $this->cloudflare->delete_project($project['cf_project_name']);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Update project
        trico()->database->update_project($project_id, array(
            'cf_project_name' => null,
            'cf_deployment_url' => null,
            'cf_last_deployment_id' => null,
            'status' => 'draft'
        ));
        
        // Delete local exports
        $this->exporter->delete_export($project['slug']);
        
        return array('success' => true);
    }
    
    /**
     * Setup custom domain
     */
    public function setup_custom_domain($project_id, $domain) {
        $project = trico()->database->get_project($project_id);
        
        if (!$project || empty($project['cf_project_name'])) {
            return new WP_Error('not_deployed', __('Deploy project first', 'trico-ai'));
        }
        
        // Add domain to CF
        $result = $this->cloudflare->add_domain($project['cf_project_name'], $domain);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Update project
        trico()->database->update_project($project_id, array(
            'custom_domain' => $domain,
            'cf_deployment_url' => "https://{$domain}"
        ));
        
        // Return CNAME info for DNS setup
        return array(
            'success' => true,
            'domain' => $domain,
            'cname_target' => $project['cf_project_name'] . '.pages.dev',
            'message' => sprintf(
                __('Add a CNAME record pointing %s to %s', 'trico-ai'),
                $domain,
                $project['cf_project_name'] . '.pages.dev'
            )
        );
    }
    
    /**
     * Get deployment status
     */
    public function get_status($project_id) {
        $project = trico()->database->get_project($project_id);
        
        if (!$project) {
            return array('status' => 'not_found');
        }
        
        if (empty($project['cf_project_name'])) {
            return array(
                'status' => 'not_deployed',
                'can_deploy' => $this->cloudflare->is_configured()
            );
        }
        
        $cf_project = $this->cloudflare->get_project($project['cf_project_name']);
        
        if (is_wp_error($cf_project)) {
            return array(
                'status' => 'error',
                'message' => $cf_project->get_error_message()
            );
        }
        
        return array(
            'status' => 'deployed',
            'url' => $project['cf_deployment_url'],
            'last_deployed' => $project['last_deployed_at'],
            'cf_project' => $cf_project['result'] ?? null
        );
    }
}
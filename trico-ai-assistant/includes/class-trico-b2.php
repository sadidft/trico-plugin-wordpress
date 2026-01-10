<?php
/**
 * Trico B2 Storage Handler
 * FIXED version - Proper URL replacement for WordPress media
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_B2 {
    
    private $key_id;
    private $app_key;
    private $bucket_id;
    private $bucket_name;
    private $auth_token;
    private $api_url;
    private $download_url;
    private $upload_url;
    private $upload_auth_token;
    
    public function __construct() {
        $this->key_id = TRICO_B2_KEY_ID;
        $this->app_key = TRICO_B2_APP_KEY;
        $this->bucket_id = TRICO_B2_BUCKET_ID;
        $this->bucket_name = TRICO_B2_BUCKET_NAME;
        
        if ($this->is_configured()) {
            $this->init_hooks();
        }
    }
    
    /**
     * Check if B2 is configured
     */
    public function is_configured() {
        return !empty($this->key_id) && 
               !empty($this->app_key) && 
               !empty($this->bucket_id) && 
               !empty($this->bucket_name);
    }
    
    /**
     * Initialize hooks for WordPress media integration
     */
    private function init_hooks() {
        // Upload handler - intercept AFTER WordPress processes
        add_filter('wp_handle_upload', array($this, 'handle_upload'), 20);
        
        // URL replacement - THIS IS THE FIX!
        add_filter('wp_get_attachment_url', array($this, 'filter_attachment_url'), 10, 2);
        add_filter('wp_get_attachment_image_src', array($this, 'filter_attachment_image_src'), 10, 4);
        add_filter('wp_calculate_image_srcset', array($this, 'filter_srcset'), 10, 5);
        
        // Content filter for old URLs
        add_filter('the_content', array($this, 'filter_content_urls'), 999);
        add_filter('post_thumbnail_html', array($this, 'filter_content_urls'), 999);
        
        // Admin media library
        add_filter('wp_prepare_attachment_for_js', array($this, 'filter_attachment_for_js'), 10, 3);
    }
    
    /**
     * Authorize with B2
     */
    public function authorize() {
        if (!empty($this->auth_token)) {
            return true;
        }
        
        $response = wp_remote_get('https://api.backblazeb2.com/b2api/v2/b2_authorize_account', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->key_id . ':' . $this->app_key)
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            trico()->core->log('B2 Auth Error: ' . $response->get_error_message(), 'error');
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body['authorizationToken'])) {
            trico()->core->log('B2 Auth Failed: ' . print_r($body, true), 'error');
            return false;
        }
        
        $this->auth_token = $body['authorizationToken'];
        $this->api_url = $body['apiUrl'];
        $this->download_url = $body['downloadUrl'];
        
        return true;
    }
    
    /**
     * Get upload URL
     */
    private function get_upload_url() {
        if (!empty($this->upload_url)) {
            return true;
        }
        
        if (!$this->authorize()) {
            return false;
        }
        
        $response = wp_remote_post($this->api_url . '/b2api/v2/b2_get_upload_url', array(
            'headers' => array(
                'Authorization' => $this->auth_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array('bucketId' => $this->bucket_id)),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            trico()->core->log('B2 Upload URL Error: ' . $response->get_error_message(), 'error');
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body['uploadUrl'])) {
            trico()->core->log('B2 Upload URL Failed: ' . print_r($body, true), 'error');
            return false;
        }
        
        $this->upload_url = $body['uploadUrl'];
        $this->upload_auth_token = $body['authorizationToken'];
        
        return true;
    }
    
    /**
     * Upload file to B2
     */
    public function upload_file($file_path, $remote_name = null) {
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', 'File not found: ' . $file_path);
        }
        
        if (!$this->get_upload_url()) {
            return new WP_Error('b2_auth_failed', 'Failed to authenticate with B2');
        }
        
        if (is_null($remote_name)) {
            $remote_name = $this->generate_remote_path($file_path);
        }
        
        $file_content = file_get_contents($file_path);
        $content_type = mime_content_type($file_path);
        $sha1 = sha1_file($file_path);
        
        $response = wp_remote_post($this->upload_url, array(
            'headers' => array(
                'Authorization' => $this->upload_auth_token,
                'Content-Type' => $content_type,
                'Content-Length' => strlen($file_content),
                'X-Bz-File-Name' => rawurlencode($remote_name),
                'X-Bz-Content-Sha1' => $sha1
            ),
            'body' => $file_content,
            'timeout' => 120
        ));
        
        // Reset upload URL for next request
        $this->upload_url = null;
        $this->upload_auth_token = null;
        
        if (is_wp_error($response)) {
            trico()->core->log('B2 Upload Error: ' . $response->get_error_message(), 'error');
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($code !== 200) {
            trico()->core->log('B2 Upload Failed: ' . print_r($body, true), 'error');
            return new WP_Error('b2_upload_failed', $body['message'] ?? 'Upload failed');
        }
        
        $b2_url = $this->download_url . '/file/' . $this->bucket_name . '/' . $remote_name;
        
        trico()->core->log('B2 Upload Success: ' . $b2_url, 'info');
        
        return array(
            'file_id' => $body['fileId'],
            'file_name' => $body['fileName'],
            'url' => $b2_url,
            'size' => $body['contentLength']
        );
    }
    
    /**
     * Generate remote path for file
     */
    private function generate_remote_path($file_path) {
        $site_slug = $this->get_site_slug();
        $filename = basename($file_path);
        $date_path = date('Y/m');
        
        return $site_slug . '/' . $date_path . '/' . $filename;
    }
    
    /**
     * Get site slug for folder organization
     */
    private function get_site_slug() {
        if (is_multisite() && get_current_blog_id() > 1) {
            $details = get_blog_details(get_current_blog_id());
            return trim($details->path, '/') ?: 'site-' . get_current_blog_id();
        }
        return 'main';
    }
    
    /**
     * Handle WordPress upload - Upload to B2 and track
     */
    public function handle_upload($upload) {
        if (!$this->is_configured()) {
            return $upload;
        }
        
        if (isset($upload['error']) && $upload['error']) {
            return $upload;
        }
        
        $file_path = $upload['file'];
        
        // Upload to B2
        $result = $this->upload_file($file_path);
        
        if (is_wp_error($result)) {
            // Log error but don't fail the upload
            trico()->core->log('B2 upload failed, using local: ' . $result->get_error_message(), 'warning');
            return $upload;
        }
        
        // Store B2 URL in upload array for later use
        $upload['trico_b2_url'] = $result['url'];
        $upload['trico_b2_file_id'] = $result['file_id'];
        
        return $upload;
    }
    
    /**
     * Filter attachment URL to return B2 URL
     * THIS IS THE KEY FIX!
     */
    public function filter_attachment_url($url, $attachment_id) {
        $b2_url = get_post_meta($attachment_id, '_trico_b2_url', true);
        
        if (!empty($b2_url)) {
            return $b2_url;
        }
        
        return $url;
    }
    
    /**
     * Filter image src for B2
     */
    public function filter_attachment_image_src($image, $attachment_id, $size, $icon) {
        if (!is_array($image)) {
            return $image;
        }
        
        $b2_url = get_post_meta($attachment_id, '_trico_b2_url', true);
        
        if (!empty($b2_url)) {
            $image[0] = $b2_url;
        }
        
        return $image;
    }
    
    /**
     * Filter srcset for responsive images
     */
    public function filter_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
        $b2_url = get_post_meta($attachment_id, '_trico_b2_url', true);
        
        if (empty($b2_url) || !is_array($sources)) {
            return $sources;
        }
        
        // Get local upload URL pattern
        $upload_dir = wp_upload_dir();
        $local_base = $upload_dir['baseurl'];
        
        // Replace local URLs with B2 base
        $b2_base = dirname($b2_url);
        
        foreach ($sources as $width => $source) {
            if (isset($source['url'])) {
                $sources[$width]['url'] = str_replace($local_base, $b2_base, $source['url']);
            }
        }
        
        return $sources;
    }
    
    /**
     * Filter content to replace local URLs with B2
     */
    public function filter_content_urls($content) {
        if (empty($content)) {
            return $content;
        }
        
        $upload_dir = wp_upload_dir();
        $local_base = $upload_dir['baseurl'];
        
        // Get B2 download URL
        if (!$this->authorize()) {
            return $content;
        }
        
        $b2_base = $this->download_url . '/file/' . $this->bucket_name . '/' . $this->get_site_slug();
        
        // Replace upload URLs
        $content = str_replace($local_base, $b2_base, $content);
        
        return $content;
    }
    
    /**
     * Filter attachment data for JavaScript (Media Library)
     */
    public function filter_attachment_for_js($response, $attachment, $meta) {
        $b2_url = get_post_meta($attachment->ID, '_trico_b2_url', true);
        
        if (!empty($b2_url)) {
            $response['url'] = $b2_url;
            
            if (isset($response['sizes'])) {
                foreach ($response['sizes'] as $size => $data) {
                    $response['sizes'][$size]['url'] = $b2_url;
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Save B2 metadata after attachment is created
     * Call this after wp_insert_attachment
     */
    public function save_attachment_meta($attachment_id, $b2_url, $b2_file_id = '') {
        update_post_meta($attachment_id, '_trico_b2_url', $b2_url);
        
        if (!empty($b2_file_id)) {
            update_post_meta($attachment_id, '_trico_b2_file_id', $b2_file_id);
        }
        
        // Also save to our tracking table
        global $wpdb;
        $table = $wpdb->base_prefix . 'trico_b2_files';
        
        $wpdb->insert($table, array(
            'site_id' => get_current_blog_id(),
            'wp_attachment_id' => $attachment_id,
            'b2_file_id' => $b2_file_id,
            'b2_file_name' => basename($b2_url),
            'b2_url' => $b2_url,
            'file_size' => 0,
            'mime_type' => get_post_mime_type($attachment_id)
        ));
    }
    
    /**
     * Delete file from B2
     */
    public function delete_file($file_id, $file_name) {
        if (!$this->authorize()) {
            return new WP_Error('b2_auth_failed', 'Failed to authenticate');
        }
        
        $response = wp_remote_post($this->api_url . '/b2api/v2/b2_delete_file_version', array(
            'headers' => array(
                'Authorization' => $this->auth_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'fileId' => $file_id,
                'fileName' => $file_name
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        
        return $code === 200;
    }
    
    /**
     * Get connection status
     */
    public function get_status() {
        if (!$this->is_configured()) {
            return array(
                'configured' => false,
                'connected' => false,
                'message' => __('B2 credentials not configured', 'trico-ai')
            );
        }
        
        $authorized = $this->authorize();
        
        return array(
            'configured' => true,
            'connected' => $authorized,
            'bucket_name' => $this->bucket_name,
            'download_url' => $this->download_url,
            'message' => $authorized 
                ? __('Connected to B2', 'trico-ai') 
                : __('Failed to connect to B2', 'trico-ai')
        );
    }
}
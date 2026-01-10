<?php
/**
 * Trico Core Class
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_Core {
    
    private $groq_models = array(
        'fast' => 'llama-3.1-8b-instant',
        'balanced' => 'llama-3.1-70b-versatile',
        'powerful' => 'llama-3.3-70b-versatile'
    );
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_frontend'));
        add_filter('body_class', array($this, 'add_body_class'));
    }
    
    public function maybe_enqueue_frontend($classes) {
        // Enqueue frontend assets jika page dibuat oleh Trico
        if ($this->is_trico_page()) {
            wp_enqueue_style('aos-css');
            wp_enqueue_script('aos-js');
        }
    }
    
    public function add_body_class($classes) {
        if ($this->is_trico_page()) {
            $classes[] = 'trico-generated';
        }
        return $classes;
    }
    
    public function is_trico_page($post_id = null) {
        if (is_null($post_id)) {
            $post_id = get_the_ID();
        }
        return (bool) get_post_meta($post_id, '_trico_generated', true);
    }
    
    public function get_model($type = 'balanced') {
        return isset($this->groq_models[$type]) 
            ? $this->groq_models[$type] 
            : $this->groq_models['balanced'];
    }
    
    public function get_all_models() {
        return $this->groq_models;
    }
    
    public function get_css_frameworks() {
        return array(
            'tailwind' => array(
                'name' => 'Tailwind CSS',
                'cdn' => 'https://cdn.tailwindcss.com',
                'type' => 'script'
            ),
            'bootstrap' => array(
                'name' => 'Bootstrap 5',
                'cdn' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
                'type' => 'style'
            ),
            'panda' => array(
                'name' => 'PandaCSS',
                'cdn' => null,
                'type' => 'build'
            ),
            'uno' => array(
                'name' => 'UnoCSS',
                'cdn' => null,
                'type' => 'build'
            ),
            'vanilla' => array(
                'name' => 'Vanilla CSS',
                'cdn' => null,
                'type' => 'none'
            )
        );
    }
    
    public function get_site_domain() {
        $custom_domain = get_option('trico_custom_domain', '');
        return !empty($custom_domain) ? $custom_domain : TRICO_DEFAULT_DOMAIN;
    }
    
    public function get_subdomain_pattern() {
        return get_option('trico_subdomain_pattern', '{project}.{domain}');
    }
    
    public function build_site_url($project_slug) {
        $domain = $this->get_site_domain();
        $pattern = $this->get_subdomain_pattern();
        
        $url = str_replace(
            array('{project}', '{domain}'),
            array($project_slug, $domain),
            $pattern
        );
        
        return 'https://' . $url;
    }
    
    public function get_environment_status() {
        return array(
            'groq_keys' => $this->count_groq_keys(),
            'cloudflare' => !empty(TRICO_CF_API_TOKEN) && !empty(TRICO_CF_ACCOUNT_ID),
            'b2_storage' => !empty(TRICO_B2_KEY_ID) && !empty(TRICO_B2_APP_KEY),
            'domain' => TRICO_DEFAULT_DOMAIN
        );
    }
    
    private function count_groq_keys() {
        $count = 0;
        for ($i = 1; $i <= 15; $i++) {
            if (!empty(getenv('GROQ_KEY_' . $i))) {
                $count++;
            }
        }
        return $count;
    }
    
    public function log($message, $type = 'info') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_entry = sprintf(
            '[Trico %s] [%s] %s',
            strtoupper($type),
            current_time('Y-m-d H:i:s'),
            is_array($message) || is_object($message) ? print_r($message, true) : $message
        );
        
        error_log($log_entry);
    }
}
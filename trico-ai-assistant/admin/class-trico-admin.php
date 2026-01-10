<?php
/**
 * Trico Admin
 * Network-wide admin pages
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_Admin {
    
    private $hook_suffix = array();
    
    public function __construct() {
        add_action('admin_menu', array($this, 'register_menus'));
        add_action('network_admin_menu', array($this, 'register_network_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    public function register_menus() {
        $this->hook_suffix['dashboard'] = add_menu_page(
            __('Trico AI', 'trico-ai'),
            __('Trico AI', 'trico-ai'),
            'edit_posts',
            'trico-dashboard',
            array($this, 'render_dashboard'),
            'data:image/svg+xml;base64,' . base64_encode($this->get_menu_icon()),
            30
        );
        
        $this->hook_suffix['generator'] = add_submenu_page(
            'trico-dashboard',
            __('Generate Website', 'trico-ai'),
            __('Generate', 'trico-ai'),
            'edit_posts',
            'trico-generator',
            array($this, 'render_generator')
        );
        
        $this->hook_suffix['projects'] = add_submenu_page(
            'trico-dashboard',
            __('Projects', 'trico-ai'),
            __('Projects', 'trico-ai'),
            'edit_posts',
            'trico-projects',
            array($this, 'render_projects')
        );
        
        $this->hook_suffix['synalytics'] = add_submenu_page(
            'trico-dashboard',
            __('Synalytics', 'trico-ai'),
            __('Synalytics', 'trico-ai'),
            'edit_posts',
            'trico-synalytics',
            array($this, 'render_synalytics')
        );
        
        $this->hook_suffix['settings'] = add_submenu_page(
            'trico-dashboard',
            __('Settings', 'trico-ai'),
            __('Settings', 'trico-ai'),
            'manage_options',
            'trico-settings',
            array($this, 'render_settings')
        );
    }
    
    public function register_network_menus() {
        add_menu_page(
            __('Trico Network', 'trico-ai'),
            __('Trico Network', 'trico-ai'),
            'manage_network',
            'trico-network',
            array($this, 'render_network_dashboard'),
            'data:image/svg+xml;base64,' . base64_encode($this->get_menu_icon()),
            30
        );
        
        add_submenu_page(
            'trico-network',
            __('API Status', 'trico-ai'),
            __('API Status', 'trico-ai'),
            'manage_network',
            'trico-api-status',
            array($this, 'render_api_status')
        );
    }
    
    public function enqueue_assets($hook) {
        if (!in_array($hook, $this->hook_suffix) && strpos($hook, 'trico') === false) {
            return;
        }
        
        wp_enqueue_style(
            'trico-admin',
            TRICO_PLUGIN_URL . 'assets/css/trico-admin.css',
            array(),
            TRICO_VERSION
        );
        
        wp_enqueue_script(
            'trico-admin',
            TRICO_PLUGIN_URL . 'assets/js/trico-admin.js',
            array('jquery', 'wp-util'),
            TRICO_VERSION,
            true
        );
        
        wp_localize_script('trico-admin', 'tricoAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('trico/v1/'),
            'nonce' => wp_create_nonce('trico_ajax'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'strings' => array(
                'generating' => __('Generating...', 'trico-ai'),
                'deploying' => __('Deploying...', 'trico-ai'),
                'success' => __('Success!', 'trico-ai'),
                'error' => __('Error occurred', 'trico-ai'),
                'confirm_delete' => __('Are you sure you want to delete this project?', 'trico-ai')
            )
        ));
    }
    
    private function get_menu_icon() {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>';
    }
    
    // ==========================================
    // RENDER PAGES
    // ==========================================
    
    public function render_dashboard() {
        $stats = trico()->database->get_stats();
        $api_status = trico()->core->get_environment_status();
        $api_usage = trico()->api_manager->get_usage_stats();
        $recent_projects = trico()->database->get_all_projects(null, 5);
        
        include TRICO_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    public function render_generator() {
        $frameworks = trico()->core->get_css_frameworks();
        $models = trico()->core->get_all_models();
        
        include TRICO_PLUGIN_DIR . 'admin/views/generator.php';
    }
    
    public function render_projects() {
        $projects = trico()->database->get_all_projects();
        
        include TRICO_PLUGIN_DIR . 'admin/views/projects.php';
    }
    
    public function render_synalytics() {
        include TRICO_PLUGIN_DIR . 'admin/views/synalytics.php';
    }
    
    public function render_settings() {
        $domain = trico()->core->get_site_domain();
        $frameworks = trico()->core->get_css_frameworks();
        
        if (isset($_POST['trico_save_settings']) && check_admin_referer('trico_settings')) {
            $this->save_settings();
        }
        
        include TRICO_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    public function render_network_dashboard() {
        $sites = get_sites(array('number' => 0));
        $total_projects = 0;
        
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            $total_projects += trico()->database->get_project_count($site->blog_id);
            restore_current_blog();
        }
        
        include TRICO_PLUGIN_DIR . 'admin/views/network-dashboard.php';
    }
    
    public function render_api_status() {
        $keys_status = trico()->api_manager->get_keys_status();
        $usage_stats = trico()->api_manager->get_usage_stats();
        $env_status = trico()->core->get_environment_status();
        
        include TRICO_PLUGIN_DIR . 'admin/views/api-status.php';
    }
    
    private function save_settings() {
        $options = array(
            'trico_custom_domain' => sanitize_text_field($_POST['trico_custom_domain'] ?? ''),
            'trico_subdomain_pattern' => sanitize_text_field($_POST['trico_subdomain_pattern'] ?? '{project}.{domain}'),
            'trico_default_framework' => sanitize_key($_POST['trico_default_framework'] ?? 'tailwind'),
            'trico_pwa_default' => isset($_POST['trico_pwa_default']) ? 1 : 0,
            'trico_analytics_default' => isset($_POST['trico_analytics_default']) ? 1 : 0,
        );
        
        foreach ($options as $key => $value) {
            update_option($key, $value);
        }
        
        add_settings_error('trico', 'settings_saved', __('Settings saved.', 'trico-ai'), 'success');
    }
}
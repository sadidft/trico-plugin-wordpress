<?php
/**
 * Trico Admin AJAX Handlers
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_Admin_Ajax {
    
    public function __construct() {
        // Generation
        add_action('wp_ajax_trico_generate', array($this, 'handle_generate'));
        add_action('wp_ajax_trico_preview', array($this, 'handle_preview'));
        add_action('wp_ajax_trico_regenerate_section', array($this, 'handle_regenerate_section'));
        
        // Projects
        add_action('wp_ajax_trico_delete_project', array($this, 'handle_delete_project'));
        add_action('wp_ajax_trico_get_project', array($this, 'handle_get_project'));
        add_action('wp_ajax_trico_update_project', array($this, 'handle_update_project'));
        
        // Deploy
        add_action('wp_ajax_trico_deploy', array($this, 'handle_deploy'));
        add_action('wp_ajax_trico_get_analytics', array($this, 'handle_get_analytics'));
        add_action('wp_ajax_trico_setup_domain', array($this, 'handle_setup_domain'));
        add_action('wp_ajax_trico_rollback', array($this, 'handle_rollback'));
        
        // API Status
        add_action('wp_ajax_trico_check_api', array($this, 'handle_check_api'));
        add_action('wp_ajax_trico_reset_api_key', array($this, 'handle_reset_api_key'));
    }
    
    /**
     * Verify AJAX request
     */
    private function verify_request($capability = 'edit_posts') {
        if (!check_ajax_referer('trico_ajax', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'trico-ai')
            ), 403);
        }
        
        if (!current_user_can($capability)) {
            wp_send_json_error(array(
                'message' => __('Permission denied', 'trico-ai')
            ), 403);
        }
    }
    
    /**
     * Handle generate request
     */
    public function handle_generate() {
        $this->verify_request();
        
        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        
        if (empty($prompt)) {
            wp_send_json_error(array(
                'message' => __('Please enter a prompt', 'trico-ai')
            ));
        }
        
        $options = array(
            'project_name' => sanitize_text_field($_POST['project_name'] ?? ''),
            'css_framework' => sanitize_key($_POST['css_framework'] ?? 'tailwind'),
            'language' => sanitize_key($_POST['language'] ?? 'id'),
            'upload_images' => !empty($_POST['upload_images']),
            'create_page' => true
        );
        
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-generator.php';
        $generator = new Trico_Generator();
        
        $result = $generator->generate($prompt, $options);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        $response = array(
            'message' => __('Website generated successfully!', 'trico-ai'),
            'project_id' => $result['project_id'],
            'page_id' => $result['page_id'],
            'generation_time' => round($result['generation_time'], 2),
            'tokens_used' => $result['tokens_used']
        );
        
        // Add edit link if page was created
        if (!empty($result['page_id'])) {
            $response['edit_url'] = get_edit_post_link($result['page_id'], 'raw');
            $response['preview_url'] = get_preview_post_link($result['page_id']);
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Handle preview request (without saving)
     */
    public function handle_preview() {
        $this->verify_request();
        
        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        
        if (empty($prompt)) {
            wp_send_json_error(array(
                'message' => __('Please enter a prompt', 'trico-ai')
            ));
        }
        
        $options = array(
            'css_framework' => sanitize_key($_POST['css_framework'] ?? 'tailwind'),
            'language' => sanitize_key($_POST['language'] ?? 'id')
        );
        
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-generator.php';
        $generator = new Trico_Generator();
        
        $result = $generator->preview($prompt, $options);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        // Build preview HTML
        $preview_html = $this->build_preview_html(
            $result['parsed'],
            $options['css_framework']
        );
        
        wp_send_json_success(array(
            'preview_html' => $preview_html,
            'blocks' => $result['parsed']['blocks'],
            'css' => $result['parsed']['css'],
            'js' => $result['parsed']['js'],
            'seo' => $result['parsed']['seo']
        ));
    }
    
    /**
     * Build complete preview HTML
     */
    private function build_preview_html($parsed, $framework) {
        $framework_css = $this->get_framework_cdn($framework);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($parsed['seo']['title'] ?? 'Preview'); ?></title>
            <?php if ($framework_css): ?>
            <?php if ($framework === 'tailwind'): ?>
            <script src="<?php echo esc_url($framework_css); ?>"></script>
            <?php else: ?>
            <link rel="stylesheet" href="<?php echo esc_url($framework_css); ?>">
            <?php endif; ?>
            <?php endif; ?>
            <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
            <style>
                * { box-sizing: border-box; }
                body { 
                    margin: 0; 
                    font-family: 'Inter', sans-serif; 
                    -webkit-font-smoothing: antialiased;
                }
                <?php echo $parsed['css']; ?>
            </style>
        </head>
        <body>
            <?php echo $parsed['blocks']; ?>
            
            <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
            <script>
                AOS.init({ duration: 800, once: true });
                <?php echo $parsed['js']; ?>
            </script>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get framework CDN URL
     */
    private function get_framework_cdn($framework) {
        $cdns = array(
            'tailwind' => 'https://cdn.tailwindcss.com',
            'bootstrap' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
            'vanilla' => null,
            'panda' => 'https://cdn.tailwindcss.com', // Fallback
            'uno' => 'https://cdn.tailwindcss.com' // Fallback
        );
        
        return isset($cdns[$framework]) ? $cdns[$framework] : null;
    }
    
    /**
     * Handle section regeneration
     */
    public function handle_regenerate_section() {
        $this->verify_request();
        
        $project_id = intval($_POST['project_id'] ?? 0);
        $section_type = sanitize_key($_POST['section_type'] ?? 'hero');
        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        
        if (!$project_id) {
            wp_send_json_error(array('message' => 'Invalid project ID'));
        }
        
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-generator.php';
        $generator = new Trico_Generator();
        
        $result = $generator->regenerate_section($project_id, $section_type, $prompt);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Handle project deletion
     */
    public function handle_delete_project() {
        $this->verify_request();
        
        $project_id = intval($_POST['project_id'] ?? 0);
        
        if (!$project_id) {
            wp_send_json_error(array('message' => 'Invalid project ID'));
        }
        
        $project = trico()->database->get_project($project_id);
        
        if (!$project) {
            wp_send_json_error(array('message' => 'Project not found'));
        }
        
        // Check ownership
        if ($project['user_id'] != get_current_user_id() && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        // Delete associated WordPress page
        if (!empty($project['post_id'])) {
            wp_delete_post($project['post_id'], true);
        }
        
        // Delete project
        $deleted = trico()->database->delete_project($project_id);
        
        if ($deleted) {
            wp_send_json_success(array('message' => 'Project deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete project'));
        }
    }
    
    /**
     * Handle get project
     */
    public function handle_get_project() {
        $this->verify_request();
        
        $project_id = intval($_POST['project_id'] ?? 0);
        
        $project = trico()->database->get_project($project_id);
        
        if (!$project) {
            wp_send_json_error(array('message' => 'Project not found'));
        }
        
        // Get history
        $history = trico()->database->get_project_history($project_id);
        
        wp_send_json_success(array(
            'project' => $project,
            'history' => $history
        ));
    }
    
    /**
     * Handle project update
     */
    public function handle_update_project() {
        $this->verify_request();
        
        $project_id = intval($_POST['project_id'] ?? 0);
        
        $project = trico()->database->get_project($project_id);
        
        if (!$project) {
            wp_send_json_error(array('message' => 'Project not found'));
        }
        
        $data = array();
        
        if (isset($_POST['name'])) {
            $data['name'] = sanitize_text_field($_POST['name']);
        }
        
        if (isset($_POST['subdomain'])) {
            $data['subdomain'] = sanitize_key($_POST['subdomain']);
        }
        
        if (isset($_POST['custom_domain'])) {
            $data['custom_domain'] = sanitize_text_field($_POST['custom_domain']);
        }
        
        if (isset($_POST['css_content'])) {
            $data['css_content'] = wp_kses_post($_POST['css_content']);
        }
        
        if (isset($_POST['js_content'])) {
            $data['js_content'] = sanitize_textarea_field($_POST['js_content']);
        }
        
        $result = trico()->database->update_project($project_id, $data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => 'Project updated'));
    }
    
    /**
     * Handle deploy request
     */
    public function handle_deploy() {
        $this->verify_request();
        
        $project_id = intval($_POST['project_id'] ?? 0);
        
        if (!$project_id) {
            wp_send_json_error(array('message' => __('Invalid project ID', 'trico-ai')));
        }
        
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-deployer.php';
        $deployer = new Trico_Deployer();
        
        $result = $deployer->deploy($project_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => __('Deployed successfully!', 'trico-ai'),
            'url' => $result['url'],
            'deploy_time' => $result['deploy_time']
        ));
    }

    /**
     * Handle get analytics
     */
    public function handle_get_analytics() {
        $this->verify_request();
        
        $project_id = intval($_POST['project_id'] ?? 0);
        $days = intval($_POST['days'] ?? 7);
        
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-analytics.php';
        $analytics = new Trico_Analytics();
        
        if ($project_id) {
            $result = $analytics->get_project_analytics($project_id, $days);
        } else {
            $result = $analytics->get_aggregated_stats($days);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }

    /**
     * Handle setup custom domain
     */
    public function handle_setup_domain() {
        $this->verify_request('manage_options');
        
        $project_id = intval($_POST['project_id'] ?? 0);
        $domain = sanitize_text_field($_POST['domain'] ?? '');
        
        if (!$project_id || empty($domain)) {
            wp_send_json_error(array('message' => __('Invalid parameters', 'trico-ai')));
        }
        
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-deployer.php';
        $deployer = new Trico_Deployer();
        
        $result = $deployer->setup_custom_domain($project_id, $domain);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }

    /**
     * Handle rollback
     */
    public function handle_rollback() {
        $this->verify_request();
        
        $project_id = intval($_POST['project_id'] ?? 0);
        $deployment_id = sanitize_text_field($_POST['deployment_id'] ?? '');
        
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-deployer.php';
        $deployer = new Trico_Deployer();
        
        $result = $deployer->rollback($project_id, $deployment_id ?: null);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Rollback successful', 'trico-ai')));
    }
    
    /**
     * Handle API status check
     */
    public function handle_check_api() {
        $this->verify_request('manage_options');
        
        $keys_status = trico()->api_manager->get_keys_status();
        $usage_stats = trico()->api_manager->get_usage_stats();
        $env_status = trico()->core->get_environment_status();
        
        wp_send_json_success(array(
            'keys' => $keys_status,
            'usage' => $usage_stats,
            'environment' => $env_status
        ));
    }
    
    /**
     * Handle API key reset
     */
    public function handle_reset_api_key() {
        $this->verify_request('manage_network');
        
        $key_index = intval($_POST['key_index'] ?? 0);
        
        if (!$key_index) {
            wp_send_json_error(array('message' => 'Invalid key index'));
        }
        
        $result = trico()->api_manager->reset_key_limit($key_index);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Key reset successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to reset key'));
        }
    }
}
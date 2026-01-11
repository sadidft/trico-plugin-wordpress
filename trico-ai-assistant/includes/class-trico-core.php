<?php
/**
 * Trico Core Class
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_Core {
    
    /**
     * Available AI Models (Updated January 2026)
     * 
     * @var array
     */
    private $groq_models = array(
        // Primary - Best for full page generation
        'powerful' => 'llama-3.3-70b-versatile',
        
        // Fast - For quick tasks like SEO, small edits
        'fast' => 'llama-3.1-8b-instant',
        
        // Balanced - Good quality with better speed
        'balanced' => 'qwen/qwen3-32b',
        
        // Alternative - DeepSeek for complex reasoning
        'reasoning' => 'deepseek-r1-distill-llama-70b',
        
        // Chat - For conversational tasks
        'chat' => 'meta-llama/llama-4-scout-17b'
    );
    
    /**
     * Model specifications
     * 
     * @var array
     */
    private $model_specs = array(
        'llama-3.3-70b-versatile' => array(
            'name' => 'Llama 3.3 70B Versatile',
            'speed' => 280,
            'context' => 131072,
            'max_completion' => 32768,
            'cost_input' => 0.59,
            'cost_output' => 0.79
        ),
        'llama-3.1-8b-instant' => array(
            'name' => 'Llama 3.1 8B Instant',
            'speed' => 560,
            'context' => 131072,
            'max_completion' => 131072,
            'cost_input' => 0.05,
            'cost_output' => 0.08
        ),
        'qwen/qwen3-32b' => array(
            'name' => 'Qwen 3 32B',
            'speed' => 400,
            'context' => 131072,
            'max_completion' => 40960,
            'cost_input' => 0.29,
            'cost_output' => 0.59
        ),
        'deepseek-r1-distill-llama-70b' => array(
            'name' => 'DeepSeek R1 Distill 70B',
            'speed' => 280,
            'context' => 131072,
            'max_completion' => 32768,
            'cost_input' => 0.59,
            'cost_output' => 0.79
        ),
        'meta-llama/llama-4-scout-17b' => array(
            'name' => 'Llama 4 Scout 17B',
            'speed' => 750,
            'context' => 131072,
            'max_completion' => 8192,
            'cost_input' => 0.11,
            'cost_output' => 0.34
        )
    );
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_frontend'));
        add_filter('body_class', array($this, 'add_body_class'));
    }
    
    public function maybe_enqueue_frontend() {
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
    
    /**
     * Get model ID by type
     * 
     * @param string $type Model type: powerful, fast, balanced, reasoning, chat
     * @return string Model ID
     */
    public function get_model($type = 'powerful') {
        // Default to powerful for unknown types
        if (!isset($this->groq_models[$type])) {
            $type = 'powerful';
        }
        
        return $this->groq_models[$type];
    }
    
    /**
     * Get all available models
     * 
     * @return array
     */
    public function get_all_models() {
        return $this->groq_models;
    }
    
    /**
     * Get model specifications
     * 
     * @param string $model_id Model ID
     * @return array|null
     */
    public function get_model_specs($model_id = null) {
        if (is_null($model_id)) {
            return $this->model_specs;
        }
        
        return isset($this->model_specs[$model_id]) ? $this->model_specs[$model_id] : null;
    }
    
    /**
     * Get best model for task
     * 
     * @param string $task Task type
     * @return string Model ID
     */
    public function get_model_for_task($task) {
        $task_mapping = array(
            'full_page' => 'powerful',
            'section' => 'balanced',
            'seo' => 'fast',
            'image_prompt' => 'fast',
            'quick_edit' => 'fast',
            'complex' => 'reasoning',
            'chat' => 'chat'
        );
        
        $type = isset($task_mapping[$task]) ? $task_mapping[$task] : 'powerful';
        
        return $this->get_model($type);
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
                'cdn' => 'https://cdn.tailwindcss.com',
                'type' => 'script'
            ),
            'uno' => array(
                'name' => 'UnoCSS',
                'cdn' => 'https://cdn.tailwindcss.com',
                'type' => 'script'
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
            'domain' => TRICO_DEFAULT_DOMAIN,
            'models' => $this->groq_models
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
    
    /**
     * Estimate cost for generation
     * 
     * @param int $input_tokens
     * @param int $output_tokens
     * @param string $model_id
     * @return float Cost in USD
     */
    public function estimate_cost($input_tokens, $output_tokens, $model_id = null) {
        if (is_null($model_id)) {
            $model_id = $this->get_model('powerful');
        }
        
        $specs = $this->get_model_specs($model_id);
        
        if (!$specs) {
            return 0;
        }
        
        $input_cost = ($input_tokens / 1000000) * $specs['cost_input'];
        $output_cost = ($output_tokens / 1000000) * $specs['cost_output'];
        
        return round($input_cost + $output_cost, 6);
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

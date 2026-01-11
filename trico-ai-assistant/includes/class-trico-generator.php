<?php
/**
 * Trico Generator
 * Main AI generation orchestrator
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_Generator {
    
    private $image_handler;
    
    public function __construct() {
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-prompts.php';
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-parser.php';
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-image.php';
        
        $this->image_handler = new Trico_Image();
    }
    
    /**
     * Generate complete website from prompt
     */
    public function generate($prompt, $options = array()) {
        $start_time = microtime(true);
        
        $defaults = array(
            'project_name' => '',
            'css_framework' => get_option('trico_default_framework', 'tailwind'),
            'language' => 'id',
            'style' => '',
            'upload_images' => false,
            'create_page' => true
        );
        
        $options = wp_parse_args($options, $defaults);
        
        trico()->core->log("Starting generation: {$prompt}", 'info');
        
        // Step 1: Build prompts
        $system_prompt = Trico_Prompts::get_system_prompt($options['css_framework']);
        $user_prompt = Trico_Prompts::build_generation_prompt($prompt, $options);
        
        // Step 2: Call AI with UPDATED MODEL
        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_prompt)
        );
        
        // Use powerful model for full page generation
        $model = trico()->core->get_model_for_task('full_page');
        
        $ai_response = trico()->api_manager->call_groq($messages, $model, array(
            'temperature' => 0.7,
            'max_tokens' => 8192
        ));
        
        if (is_wp_error($ai_response)) {
            return $ai_response;
        }
        
        // Step 3: Extract content from response
        $content = isset($ai_response['choices'][0]['message']['content']) 
            ? $ai_response['choices'][0]['message']['content'] 
            : '';
        
        if (empty($content)) {
            return new WP_Error('empty_response', __('AI returned empty response', 'trico-ai'));
        }
        
        // Step 4: Parse response
        $parsed = Trico_Parser::parse($content);
        
        if (!empty($parsed['errors'])) {
            trico()->core->log('Parse errors: ' . implode(', ', $parsed['errors']), 'warning');
        }
        
        // Step 5: Generate images
        $image_placeholders = Trico_Parser::get_image_placeholders($parsed['blocks']);
        
        if (!empty($image_placeholders)) {
            $image_urls = $this->image_handler->batch_generate(
                $image_placeholders,
                $parsed['images'],
                $options['upload_images']
            );
            
            $parsed['blocks'] = Trico_Parser::replace_image_placeholders(
                $parsed['blocks'],
                $image_urls
            );
            
            $parsed['image_urls'] = $image_urls;
        }
        
        // Step 6: Create WordPress Page
        $page_id = null;
        
        if ($options['create_page']) {
            $page_id = $this->create_wordpress_page($parsed, $options);
            
            if (is_wp_error($page_id)) {
                trico()->core->log('Failed to create page: ' . $page_id->get_error_message(), 'error');
            } else {
                $parsed['page_id'] = $page_id;
            }
        }
        
        // Step 7: Save to project
        $project_name = !empty($options['project_name']) 
            ? $options['project_name'] 
            : $this->generate_project_name($prompt);
        
        $project_id = $this->save_project($parsed, $project_name, $options);
        
        if (is_wp_error($project_id)) {
            return $project_id;
        }
        
        // Step 8: Save to history
        $generation_time = microtime(true) - $start_time;
        $tokens_used = $ai_response['usage']['total_tokens'] ?? 0;
        
        trico()->database->add_history($project_id, array(
            'prompt' => $prompt,
            'blocks_content' => $parsed['blocks'],
            'css_content' => $parsed['css'],
            'js_content' => $parsed['js'],
            'image_prompts' => json_encode($parsed['images']),
            'ai_model' => $model,
            'generation_time' => $generation_time,
            'tokens_used' => $tokens_used
        ));
        
        // Calculate cost
        $input_tokens = $ai_response['usage']['prompt_tokens'] ?? 0;
        $output_tokens = $ai_response['usage']['completion_tokens'] ?? 0;
        $estimated_cost = trico()->core->estimate_cost($input_tokens, $output_tokens, $model);
        
        trico()->core->log("Generation complete in {$generation_time}s, cost: \${$estimated_cost}", 'info');
        
        return array(
            'success' => true,
            'project_id' => $project_id,
            'page_id' => $page_id,
            'parsed' => $parsed,
            'generation_time' => $generation_time,
            'tokens_used' => $tokens_used,
            'model_used' => $model,
            'estimated_cost' => $estimated_cost
        );
    }
    
    /**
     * Create WordPress page with generated content
     */
    private function create_wordpress_page($parsed, $options) {
        $title = !empty($parsed['seo']['title']) 
            ? $parsed['seo']['title'] 
            : (!empty($options['project_name']) ? $options['project_name'] : 'Generated Page');
        
        $page_data = array(
            'post_title' => sanitize_text_field($title),
            'post_content' => $parsed['blocks'],
            'post_status' => 'draft',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
            'meta_input' => array(
                '_trico_generated' => 1,
                '_trico_css' => $parsed['css'],
                '_trico_js' => $parsed['js'],
                '_trico_seo_title' => $parsed['seo']['title'] ?? '',
                '_trico_seo_description' => $parsed['seo']['description'] ?? '',
                '_trico_framework' => $options['css_framework']
            )
        );
        
        $page_id = wp_insert_post($page_data);
        
        if (!is_wp_error($page_id)) {
            $this->inject_page_css($page_id, $parsed['css'], $options['css_framework']);
        }
        
        return $page_id;
    }
    
    /**
     * Inject CSS for the page
     */
    private function inject_page_css($page_id, $css, $framework) {
        if (empty($css)) {
            return;
        }
        
        update_post_meta($page_id, '_trico_custom_css', $css);
        
        if (current_user_can('manage_options')) {
            $existing_css = wp_get_custom_css();
            $marker_start = "/* TRICO PAGE {$page_id} START */";
            $marker_end = "/* TRICO PAGE {$page_id} END */";
            
            $existing_css = preg_replace(
                '/' . preg_quote($marker_start, '/') . '.*?' . preg_quote($marker_end, '/') . '/s',
                '',
                $existing_css
            );
            
            $new_css = $existing_css . "\n\n{$marker_start}\n{$css}\n{$marker_end}";
            
            wp_update_custom_css_post($new_css);
        }
    }
    
    /**
     * Save project to database
     */
    private function save_project($parsed, $name, $options) {
        $data = array(
            'name' => sanitize_text_field($name),
            'slug' => sanitize_title($name),
            'css_content' => $parsed['css'],
            'js_content' => $parsed['js'],
            'seo_title' => $parsed['seo']['title'] ?? '',
            'seo_description' => $parsed['seo']['description'] ?? '',
            'css_framework' => $options['css_framework'],
            'post_id' => $parsed['page_id'] ?? null,
            'status' => 'draft'
        );
        
        return trico()->database->create_project($data);
    }
    
    /**
     * Generate project name from prompt
     */
    private function generate_project_name($prompt) {
        $prompt = strip_tags($prompt);
        $words = preg_split('/\s+/', $prompt);
        $words = array_slice($words, 0, 5);
        
        $name = implode(' ', $words);
        $name = ucwords(strtolower($name));
        
        if (strlen($name) > 50) {
            $name = substr($name, 0, 47) . '...';
        }
        
        return $name ?: 'Untitled Project';
    }
    
    /**
     * Regenerate specific section
     */
    public function regenerate_section($project_id, $section_type, $prompt) {
        $project = trico()->database->get_project($project_id);
        
        if (!$project) {
            return new WP_Error('not_found', __('Project not found', 'trico-ai'));
        }
        
        $system_prompt = Trico_Prompts::get_partial_update_prompt($section_type);
        
        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $prompt)
        );
        
        // Use balanced model for sections (faster)
        $model = trico()->core->get_model_for_task('section');
        
        $ai_response = trico()->api_manager->call_groq(
            $messages,
            $model,
            array('max_tokens' => 4096)
        );
        
        if (is_wp_error($ai_response)) {
            return $ai_response;
        }
        
        $content = $ai_response['choices'][0]['message']['content'] ?? '';
        $parsed = Trico_Parser::parse($content);
        
        return array(
            'success' => true,
            'section' => $parsed,
            'model_used' => $model
        );
    }
    
    /**
     * Generate SEO metadata
     */
    public function generate_seo($content, $business_info = '') {
        $system_prompt = Trico_Prompts::get_seo_prompt();
        
        $user_prompt = "Generate SEO for this website:\n\n{$content}";
        
        if (!empty($business_info)) {
            $user_prompt .= "\n\nBusiness Info: {$business_info}";
        }
        
        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_prompt)
        );
        
        // Use fast model for SEO
        $model = trico()->core->get_model_for_task('seo');
        
        $ai_response = trico()->api_manager->call_groq(
            $messages,
            $model,
            array('max_tokens' => 1024)
        );
        
        if (is_wp_error($ai_response)) {
            return $ai_response;
        }
        
        $content = $ai_response['choices'][0]['message']['content'] ?? '';
        
        $seo = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $seo = Trico_Parser::parse("===SEO===\n{$content}\n===SEO_END===")['seo'];
        }
        
        return $seo;
    }
    
    /**
     * Preview without saving
     */
    public function preview($prompt, $options = array()) {
        $options['create_page'] = false;
        $options['upload_images'] = false;
        
        return $this->generate($prompt, $options);
    }
}

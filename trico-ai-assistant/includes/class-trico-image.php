<?php
/**
 * Trico Image Generator
 * Pollinations.ai integration for AI image generation
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_Image {
    
    private $base_url = 'https://image.pollinations.ai/prompt/';
    private $default_width = 1200;
    private $default_height = 800;
    
    /**
     * Generate image URL from prompt
     */
    public function generate_url($prompt, $options = array()) {
        $defaults = array(
            'width' => $this->default_width,
            'height' => $this->default_height,
            'seed' => null,
            'model' => 'flux', // flux, turbo
            'nologo' => true
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Clean and encode prompt
        $prompt = $this->prepare_prompt($prompt);
        $encoded_prompt = rawurlencode($prompt);
        
        // Build URL
        $url = $this->base_url . $encoded_prompt;
        
        // Add parameters
        $params = array();
        
        if ($options['width']) {
            $params['width'] = intval($options['width']);
        }
        
        if ($options['height']) {
            $params['height'] = intval($options['height']);
        }
        
        if ($options['seed']) {
            $params['seed'] = intval($options['seed']);
        }
        
        if ($options['model']) {
            $params['model'] = sanitize_key($options['model']);
        }
        
        if ($options['nologo']) {
            $params['nologo'] = 'true';
        }
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    /**
     * Generate multiple images from placeholder array
     */
    public function generate_from_placeholders($placeholders, $image_descriptions) {
        $results = array();
        
        foreach ($placeholders as $placeholder) {
            $description = isset($image_descriptions[$placeholder]) 
                ? $image_descriptions[$placeholder] 
                : $this->get_default_description($placeholder);
            
            $size = $this->get_size_for_placeholder($placeholder);
            
            $results[$placeholder] = $this->generate_url($description, array(
                'width' => $size['width'],
                'height' => $size['height'],
                'seed' => $this->generate_consistent_seed($placeholder)
            ));
        }
        
        return $results;
    }
    
    /**
     * Prepare prompt for URL encoding
     */
    private function prepare_prompt($prompt) {
        // Clean the prompt
        $prompt = strip_tags($prompt);
        $prompt = trim($prompt);
        
        // Add quality enhancers if not present
        $quality_keywords = array('high quality', '4k', 'professional', 'detailed');
        $has_quality = false;
        
        foreach ($quality_keywords as $keyword) {
            if (stripos($prompt, $keyword) !== false) {
                $has_quality = true;
                break;
            }
        }
        
        if (!$has_quality) {
            $prompt .= ', high quality, professional photography, detailed';
        }
        
        // Limit length
        if (strlen($prompt) > 500) {
            $prompt = substr($prompt, 0, 497) . '...';
        }
        
        return $prompt;
    }
    
    /**
     * Get size based on placeholder name
     */
    private function get_size_for_placeholder($placeholder) {
        $sizes = array(
            'HERO' => array('width' => 1920, 'height' => 1080),
            'FEATURE' => array('width' => 800, 'height' => 600),
            'ABOUT' => array('width' => 1200, 'height' => 800),
            'TESTIMONIAL' => array('width' => 400, 'height' => 400),
            'ICON' => array('width' => 256, 'height' => 256),
            'LOGO' => array('width' => 512, 'height' => 512),
            'BACKGROUND' => array('width' => 1920, 'height' => 1080),
            'PRODUCT' => array('width' => 800, 'height' => 800),
            'TEAM' => array('width' => 600, 'height' => 600),
            'GALLERY' => array('width' => 1200, 'height' => 800),
        );
        
        foreach ($sizes as $key => $size) {
            if (strpos($placeholder, $key) !== false) {
                return $size;
            }
        }
        
        // Default
        return array('width' => $this->default_width, 'height' => $this->default_height);
    }
    
    /**
     * Get default description based on placeholder name
     */
    private function get_default_description($placeholder) {
        $defaults = array(
            'HERO_IMAGE' => 'Modern business hero image, professional team working, bright office, natural lighting',
            'FEATURE_1_IMAGE' => 'Abstract technology concept, modern minimalist style, blue gradient',
            'FEATURE_2_IMAGE' => 'Business innovation concept, clean design, professional',
            'FEATURE_3_IMAGE' => 'Customer service excellence, friendly professional, modern office',
            'ABOUT_IMAGE' => 'Professional team photo, modern office environment, natural lighting',
            'CTA_IMAGE' => 'Inspiring business success image, modern aesthetic',
            'BACKGROUND_IMAGE' => 'Abstract gradient background, modern, professional, subtle pattern'
        );
        
        return isset($defaults[$placeholder]) 
            ? $defaults[$placeholder] 
            : 'Professional business image, modern, high quality';
    }
    
    /**
     * Generate consistent seed from placeholder name
     * This ensures same placeholder generates same image across regenerations
     */
    private function generate_consistent_seed($placeholder) {
        return abs(crc32($placeholder . date('Y-m-d')));
    }
    
    /**
     * Download image and upload to B2/WordPress
     */
    public function download_and_upload($image_url, $filename = null) {
        if (is_null($filename)) {
            $filename = 'trico-' . uniqid() . '.jpg';
        }
        
        // Download image
        $response = wp_remote_get($image_url, array(
            'timeout' => 60,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            trico()->core->log('Image download failed: ' . $response->get_error_message(), 'error');
            return $response;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        
        if (empty($image_data)) {
            return new WP_Error('empty_image', 'Downloaded image is empty');
        }
        
        // Get upload directory
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        // Save to disk
        $saved = file_put_contents($file_path, $image_data);
        
        if ($saved === false) {
            return new WP_Error('save_failed', 'Failed to save image to disk');
        }
        
        // Create attachment
        $attachment = array(
            'post_mime_type' => 'image/jpeg',
            'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attach_id = wp_insert_attachment($attachment, $file_path);
        
        if (is_wp_error($attach_id)) {
            @unlink($file_path);
            return $attach_id;
        }
        
        // Generate metadata
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        // Return full URL
        return array(
            'id' => $attach_id,
            'url' => wp_get_attachment_url($attach_id)
        );
    }
    
    /**
     * Batch generate and optionally upload images
     */
    public function batch_generate($placeholders, $descriptions, $upload = false) {
        $results = array();
        
        foreach ($placeholders as $placeholder) {
            $description = isset($descriptions[$placeholder]) 
                ? $descriptions[$placeholder] 
                : $this->get_default_description($placeholder);
            
            $url = $this->generate_url($description, array(
                'width' => $this->get_size_for_placeholder($placeholder)['width'],
                'height' => $this->get_size_for_placeholder($placeholder)['height']
            ));
            
            if ($upload) {
                $uploaded = $this->download_and_upload($url, 'trico-' . strtolower($placeholder) . '-' . uniqid() . '.jpg');
                
                if (!is_wp_error($uploaded)) {
                    $results[$placeholder] = $uploaded['url'];
                } else {
                    // Fallback to direct URL
                    $results[$placeholder] = $url;
                    trico()->core->log("Upload failed for {$placeholder}, using direct URL", 'warning');
                }
            } else {
                $results[$placeholder] = $url;
            }
        }
        
        return $results;
    }
}
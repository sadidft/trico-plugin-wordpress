<?php
/**
 * Trico Frontend Handler
 * Injects CSS/JS for generated pages
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_head', array($this, 'inject_custom_css'), 100);
        add_action('wp_footer', array($this, 'inject_custom_js'), 100);
        add_action('wp_head', array($this, 'inject_seo_meta'), 5);
        add_filter('document_title_parts', array($this, 'filter_title'));
    }
    
    /**
     * Check if current page is Trico generated
     */
    private function is_trico_page() {
        if (!is_singular('page')) {
            return false;
        }
        
        return (bool) get_post_meta(get_the_ID(), '_trico_generated', true);
    }
    
    /**
     * Enqueue frontend assets for Trico pages
     */
    public function enqueue_assets() {
        if (!$this->is_trico_page()) {
            return;
        }
        
        $post_id = get_the_ID();
        $framework = get_post_meta($post_id, '_trico_framework', true) ?: 'tailwind';
        
        // Framework CSS/JS
        $this->enqueue_framework($framework);
        
        // AOS Animation library
        wp_enqueue_style(
            'aos',
            'https://unpkg.com/aos@2.3.1/dist/aos.css',
            array(),
            '2.3.1'
        );
        
        wp_enqueue_script(
            'aos',
            'https://unpkg.com/aos@2.3.1/dist/aos.js',
            array(),
            '2.3.1',
            true
        );
        
        // Trico frontend script
        wp_enqueue_script(
            'trico-frontend',
            TRICO_PLUGIN_URL . 'assets/js/trico-frontend.js',
            array('aos'),
            TRICO_VERSION,
            true
        );
    }
    
    /**
     * Enqueue CSS framework
     */
    private function enqueue_framework($framework) {
        switch ($framework) {
            case 'tailwind':
                wp_enqueue_script(
                    'tailwindcss',
                    'https://cdn.tailwindcss.com',
                    array(),
                    null,
                    false
                );
                break;
                
            case 'bootstrap':
                wp_enqueue_style(
                    'bootstrap',
                    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
                    array(),
                    '5.3.3'
                );
                wp_enqueue_script(
                    'bootstrap',
                    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
                    array(),
                    '5.3.3',
                    true
                );
                break;
                
            case 'panda':
            case 'uno':
                // Fallback to Tailwind CDN for these
                wp_enqueue_script(
                    'tailwindcss',
                    'https://cdn.tailwindcss.com',
                    array(),
                    null,
                    false
                );
                break;
                
            case 'vanilla':
            default:
                // No framework needed
                break;
        }
    }
    
    /**
     * Inject custom CSS in head
     */
    public function inject_custom_css() {
        if (!$this->is_trico_page()) {
            return;
        }
        
        $post_id = get_the_ID();
        $custom_css = get_post_meta($post_id, '_trico_css', true);
        
        if (empty($custom_css)) {
            $custom_css = get_post_meta($post_id, '_trico_custom_css', true);
        }
        
        if (!empty($custom_css)) {
            echo "\n<!-- Trico Custom CSS -->\n";
            echo "<style id=\"trico-custom-css\">\n";
            echo wp_strip_all_tags($custom_css);
            echo "\n</style>\n";
        }
    }
    
    /**
     * Inject custom JS in footer
     */
    public function inject_custom_js() {
        if (!$this->is_trico_page()) {
            return;
        }
        
        $post_id = get_the_ID();
        $custom_js = get_post_meta($post_id, '_trico_js', true);
        
        // Initialize AOS
        echo "\n<!-- Trico Scripts -->\n";
        echo "<script>\n";
        echo "document.addEventListener('DOMContentLoaded', function() {\n";
        echo "  if (typeof AOS !== 'undefined') {\n";
        echo "    AOS.init({ duration: 800, once: true, offset: 50 });\n";
        echo "  }\n";
        
        if (!empty($custom_js)) {
            echo "\n  // Custom JS\n";
            echo "  " . $custom_js . "\n";
        }
        
        echo "});\n";
        echo "</script>\n";
    }
    
    /**
     * Inject SEO meta tags
     */
    public function inject_seo_meta() {
        if (!$this->is_trico_page()) {
            return;
        }
        
        $post_id = get_the_ID();
        
        $seo_title = get_post_meta($post_id, '_trico_seo_title', true);
        $seo_description = get_post_meta($post_id, '_trico_seo_description', true);
        $og_image = get_post_meta($post_id, '_trico_og_image', true);
        
        echo "\n<!-- Trico SEO -->\n";
        
        // Meta description
        if (!empty($seo_description)) {
            echo '<meta name="description" content="' . esc_attr($seo_description) . '">' . "\n";
        }
        
        // Open Graph
        echo '<meta property="og:type" content="website">' . "\n";
        
        if (!empty($seo_title)) {
            echo '<meta property="og:title" content="' . esc_attr($seo_title) . '">' . "\n";
        }
        
        if (!empty($seo_description)) {
            echo '<meta property="og:description" content="' . esc_attr($seo_description) . '">' . "\n";
        }
        
        echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
        
        if (!empty($og_image)) {
            echo '<meta property="og:image" content="' . esc_url($og_image) . '">' . "\n";
        }
        
        // Twitter Card
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        
        if (!empty($seo_title)) {
            echo '<meta name="twitter:title" content="' . esc_attr($seo_title) . '">' . "\n";
        }
        
        if (!empty($seo_description)) {
            echo '<meta name="twitter:description" content="' . esc_attr($seo_description) . '">' . "\n";
        }
        
        if (!empty($og_image)) {
            echo '<meta name="twitter:image" content="' . esc_url($og_image) . '">' . "\n";
        }
        
        // Canonical
        echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '">' . "\n";
    }
    
    /**
     * Filter page title
     */
    public function filter_title($title_parts) {
        if (!$this->is_trico_page()) {
            return $title_parts;
        }
        
        $seo_title = get_post_meta(get_the_ID(), '_trico_seo_title', true);
        
        if (!empty($seo_title)) {
            $title_parts['title'] = $seo_title;
        }
        
        return $title_parts;
    }
}
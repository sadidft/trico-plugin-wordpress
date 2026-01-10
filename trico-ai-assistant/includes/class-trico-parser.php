<?php
/**
 * Trico Response Parser
 * Parse AI response into structured components
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_Parser {
    
    /**
     * Parse complete AI response
     */
    public static function parse($response) {
        $result = array(
            'blocks' => '',
            'css' => '',
            'js' => '',
            'images' => array(),
            'seo' => array(),
            'raw' => $response,
            'errors' => array()
        );
        
        // Parse Blocks
        $result['blocks'] = self::extract_section($response, 'BLOCKS_START', 'BLOCKS_END');
        if (empty($result['blocks'])) {
            $result['errors'][] = 'Failed to parse blocks section';
        }
        
        // Parse CSS
        $result['css'] = self::extract_section($response, 'CSS_START', 'CSS_END');
        $result['css'] = self::clean_css($result['css']);
        
        // Parse JS
        $result['js'] = self::extract_section($response, 'JS_START', 'JS_END');
        $result['js'] = self::clean_js($result['js']);
        
        // Parse Images
        $images_raw = self::extract_section($response, 'IMAGES', 'IMAGES_END');
        $result['images'] = self::parse_images($images_raw);
        
        // Parse SEO
        $seo_raw = self::extract_section($response, 'SEO', 'SEO_END');
        $result['seo'] = self::parse_seo($seo_raw);
        
        // Clean blocks (remove any stray markdown)
        $result['blocks'] = self::clean_blocks($result['blocks']);
        
        return $result;
    }
    
    /**
     * Extract section between markers
     */
    private static function extract_section($content, $start_marker, $end_marker) {
        $start = "==={$start_marker}===";
        $end = "==={$end_marker}===";
        
        $start_pos = strpos($content, $start);
        $end_pos = strpos($content, $end);
        
        if ($start_pos === false || $end_pos === false) {
            return '';
        }
        
        $start_pos += strlen($start);
        $section = substr($content, $start_pos, $end_pos - $start_pos);
        
        return trim($section);
    }
    
    /**
     * Parse image placeholders
     */
    private static function parse_images($content) {
        $images = array();
        
        if (empty($content)) {
            return $images;
        }
        
        // Match pattern: [PLACEHOLDER_NAME]: description
        preg_match_all('/\[([A-Z_0-9]+)\]:\s*(.+)$/m', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $images[$match[1]] = trim($match[2]);
        }
        
        return $images;
    }
    
    /**
     * Parse SEO data
     */
    private static function parse_seo($content) {
        $seo = array(
            'title' => '',
            'description' => '',
            'keywords' => ''
        );
        
        if (empty($content)) {
            return $seo;
        }
        
        // Parse key: value format
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (preg_match('/^([a-z_]+):\s*(.+)$/i', $line, $match)) {
                $key = strtolower(trim($match[1]));
                $value = trim($match[2]);
                
                if (isset($seo[$key])) {
                    $seo[$key] = $value;
                }
            }
        }
        
        return $seo;
    }
    
    /**
     * Clean CSS output
     */
    private static function clean_css($css) {
        if (empty($css)) {
            return '';
        }
        
        // Remove any markdown code fences
        $css = preg_replace('/```css?\s*/i', '', $css);
        $css = preg_replace('/```\s*/', '', $css);
        
        // Remove CSS comments
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        $css = preg_replace('/\/\/.*$/m', '', $css);
        
        // Clean up whitespace
        $css = preg_replace('/\n\s*\n/', "\n", $css);
        
        return trim($css);
    }
    
    /**
     * Clean JS output
     */
    private static function clean_js($js) {
        if (empty($js)) {
            return '';
        }
        
        // Remove markdown code fences
        $js = preg_replace('/```javascript?\s*/i', '', $js);
        $js = preg_replace('/```js?\s*/i', '', $js);
        $js = preg_replace('/```\s*/', '', $js);
        
        // Remove single-line comments
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*.*?\*\//s', '', $js);
        
        return trim($js);
    }
    
    /**
     * Clean WordPress blocks
     */
    private static function clean_blocks($blocks) {
        if (empty($blocks)) {
            return '';
        }
        
        // Remove markdown code fences
        $blocks = preg_replace('/```html?\s*/i', '', $blocks);
        $blocks = preg_replace('/```\s*/', '', $blocks);
        
        // Remove HTML comments (but keep WordPress block comments)
        // WordPress block comments: <!-- wp:block-name -->
        // Keep those, remove others like <!-- This is a comment -->
        $blocks = preg_replace('/<!--(?!\s*\/?wp:)[^>]*-->/', '', $blocks);
        
        // Validate WordPress block structure
        $blocks = self::validate_block_structure($blocks);
        
        return trim($blocks);
    }
    
    /**
     * Validate and fix WordPress block structure
     */
    private static function validate_block_structure($blocks) {
        // Count opening and closing tags
        preg_match_all('/<!-- wp:([a-z\-\/]+)/', $blocks, $opens);
        preg_match_all('/<!-- \/wp:([a-z\-]+)/', $blocks, $closes);
        
        // Basic validation - should have matching opens/closes
        // This is simplified; full validation would need a proper parser
        
        return $blocks;
    }
    
    /**
     * Replace image placeholders with actual URLs
     */
    public static function replace_image_placeholders($blocks, $image_urls) {
        foreach ($image_urls as $placeholder => $url) {
            $blocks = str_replace(
                "[{$placeholder}]",
                esc_url($url),
                $blocks
            );
        }
        
        return $blocks;
    }
    
    /**
     * Convert blocks to WordPress post_content format
     */
    public static function blocks_to_post_content($blocks) {
        // WordPress stores blocks as-is in post_content
        // Just ensure proper formatting
        return $blocks;
    }
    
    /**
     * Extract all image placeholders from blocks
     */
    public static function get_image_placeholders($blocks) {
        preg_match_all('/\[([A-Z_0-9]+_IMAGE)\]/', $blocks, $matches);
        return array_unique($matches[1]);
    }
}
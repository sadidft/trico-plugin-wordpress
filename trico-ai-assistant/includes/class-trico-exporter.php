<?php
/**
 * Trico Static Exporter
 * Export WordPress page to static HTML
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_Exporter {
    
    private $export_dir;
    private $export_url;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->export_dir = $upload_dir['basedir'] . '/trico-exports/';
        $this->export_url = $upload_dir['baseurl'] . '/trico-exports/';
    }
    
    /**
     * Export a project to static files
     */
    public function export_project($project_id) {
        $project = trico()->database->get_project($project_id);
        
        if (!$project) {
            return new WP_Error('not_found', 'Project not found');
        }
        
        if (empty($project['post_id'])) {
            return new WP_Error('no_page', 'Project has no associated page');
        }
        
        $post_id = $project['post_id'];
        $slug = $project['slug'];
        
        // Create export directory
        $project_dir = $this->export_dir . $slug . '/';
        
        if (!$this->ensure_directory($project_dir)) {
            return new WP_Error('dir_failed', 'Failed to create export directory');
        }
        
        // Get page content
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('post_not_found', 'Page not found');
        }
        
        // Build static HTML
        $html = $this->build_html($post, $project);
        
        // Save index.html
        $index_path = $project_dir . 'index.html';
        
        if (file_put_contents($index_path, $html) === false) {
            return new WP_Error('write_failed', 'Failed to write index.html');
        }
        
        // Generate sitemap
        $this->generate_sitemap($project_dir, $project);
        
        // Generate robots.txt
        $this->generate_robots($project_dir, $project);
        
        // Generate manifest for PWA (if enabled)
        if (!empty($project['pwa_enabled'])) {
            $this->generate_pwa_manifest($project_dir, $project);
        }
        
        trico()->core->log("Exported project {$project_id} to {$project_dir}", 'info');
        
        return array(
            'success' => true,
            'path' => $project_dir,
            'url' => $this->export_url . $slug . '/',
            'files' => array(
                'index.html',
                'sitemap.xml',
                'robots.txt'
            )
        );
    }
    
    /**
     * Build complete HTML document
     */
    private function build_html($post, $project) {
        $framework = $project['css_framework'] ?? 'tailwind';
        $custom_css = $project['css_content'] ?? '';
        $custom_js = $project['js_content'] ?? '';
        $seo_title = $project['seo_title'] ?? $post->post_title;
        $seo_description = $project['seo_description'] ?? '';
        
        // Process blocks to HTML
        $content = apply_filters('the_content', $post->post_content);
        
        // Get framework includes
        $framework_head = $this->get_framework_head($framework);
        $framework_foot = $this->get_framework_foot($framework);
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$seo_title}</title>
    <meta name="description" content="{$seo_description}">
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{$seo_title}">
    <meta property="og:description" content="{$seo_description}">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{$seo_title}">
    <meta name="twitter:description" content="{$seo_description}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    {$framework_head}
    
    <!-- Custom CSS -->
    <style>
        * { box-sizing: border-box; }
        body { 
            margin: 0; 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        img { max-width: 100%; height: auto; }
        
        {$custom_css}
    </style>
</head>
<body>
    {$content}
    
    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    {$framework_foot}
    
    <!-- Initialize -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({ duration: 800, once: true, offset: 50 });
            {$custom_js}
        });
    </script>
</body>
</html>
HTML;
        
        // Minify HTML
        $html = $this->minify_html($html);
        
        return $html;
    }
    
    /**
     * Get framework head includes
     */
    private function get_framework_head($framework) {
        switch ($framework) {
            case 'tailwind':
            case 'panda':
            case 'uno':
                return '<script src="https://cdn.tailwindcss.com"></script>';
                
            case 'bootstrap':
                return '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
                
            default:
                return '';
        }
    }
    
    /**
     * Get framework footer includes
     */
    private function get_framework_foot($framework) {
        if ($framework === 'bootstrap') {
            return '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>';
        }
        return '';
    }
    
    /**
     * Generate sitemap.xml
     */
    private function generate_sitemap($dir, $project) {
        $url = trico()->core->build_site_url($project['subdomain'] ?? $project['slug']);
        $lastmod = date('c', strtotime($project['updated_at']));
        
        $sitemap = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{$url}</loc>
        <lastmod>{$lastmod}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
</urlset>
XML;
        
        file_put_contents($dir . 'sitemap.xml', $sitemap);
    }
    
    /**
     * Generate robots.txt
     */
    private function generate_robots($dir, $project) {
        $url = trico()->core->build_site_url($project['subdomain'] ?? $project['slug']);
        
        $robots = <<<TXT
User-agent: *
Allow: /

Sitemap: {$url}/sitemap.xml
TXT;
        
        file_put_contents($dir . 'robots.txt', $robots);
    }
    
    /**
     * Generate PWA manifest
     */
    private function generate_pwa_manifest($dir, $project) {
        $name = $project['name'];
        $short_name = substr($project['name'], 0, 12);
        
        $manifest = array(
            'name' => $name,
            'short_name' => $short_name,
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#6366f1',
            'icons' => array(
                array(
                    'src' => '/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ),
                array(
                    'src' => '/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png'
                )
            )
        );
        
        file_put_contents(
            $dir . 'manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
    
    /**
     * Minify HTML
     */
    private function minify_html($html) {
        // Remove HTML comments (except conditional)
        $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);
        
        // Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);
        
        // Remove multiple spaces
        $html = preg_replace('/\s+/', ' ', $html);
        
        return trim($html);
    }
    
    /**
     * Ensure directory exists
     */
    private function ensure_directory($dir) {
        if (!file_exists($dir)) {
            return wp_mkdir_p($dir);
        }
        return is_writable($dir);
    }
    
    /**
     * Get exported files for a project
     */
    public function get_export_files($project_slug) {
        $dir = $this->export_dir . $project_slug . '/';
        
        if (!is_dir($dir)) {
            return array();
        }
        
        $files = array();
        $iterator = new DirectoryIterator($dir);
        
        foreach ($iterator as $file) {
            if ($file->isDot()) continue;
            
            $files[] = array(
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'modified' => $file->getMTime()
            );
        }
        
        return $files;
    }
    
    /**
     * Delete export
     */
    public function delete_export($project_slug) {
        $dir = $this->export_dir . $project_slug . '/';
        
        if (!is_dir($dir)) {
            return true;
        }
        
        // Recursively delete
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        return rmdir($dir);
    }
}
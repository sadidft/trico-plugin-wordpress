<?php
/**
 * Trico AI Assistant Uninstall
 * 
 * Clean up all plugin data on uninstall.
 * 
 * @package Trico_AI_Assistant
 */

// Exit if not called by WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up function
 */
function trico_uninstall_cleanup() {
    global $wpdb;
    
    // Check if we should delete data
    $delete_data = get_option('trico_delete_data_on_uninstall', false);
    
    if (!$delete_data) {
        // Keep data, just deactivate
        return;
    }
    
    // Delete plugin options
    $options_to_delete = array(
        'trico_version',
        'trico_installed_at',
        'trico_custom_domain',
        'trico_subdomain_pattern',
        'trico_default_framework',
        'trico_pwa_default',
        'trico_analytics_default',
        'trico_api_rate_limits',
        'trico_delete_data_on_uninstall'
    );
    
    foreach ($options_to_delete as $option) {
        delete_option($option);
        delete_site_option($option);
    }
    
    // Delete custom tables
    $tables = array(
        $wpdb->base_prefix . 'trico_projects',
        $wpdb->base_prefix . 'trico_history',
        $wpdb->base_prefix . 'trico_b2_files'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
    
    // Delete post meta
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_trico_%'");
    
    // Delete Trico-generated pages (optional, commented out for safety)
    // $wpdb->query("DELETE FROM {$wpdb->posts} WHERE ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_trico_generated')");
    
    // Clean up exports directory
    $upload_dir = wp_upload_dir();
    $export_dir = $upload_dir['basedir'] . '/trico-exports/';
    
    if (is_dir($export_dir)) {
        trico_recursive_delete($export_dir);
    }
    
    // Remove custom CSS entries from Additional CSS
    $custom_css = wp_get_custom_css();
    $custom_css = preg_replace('/\/\* TRICO PAGE \d+ START \*\/.*?\/\* TRICO PAGE \d+ END \*\//s', '', $custom_css);
    wp_update_custom_css_post($custom_css);
    
    // Multisite: Clean up all sites
    if (is_multisite()) {
        $sites = get_sites(array('number' => 0));
        
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            
            foreach ($options_to_delete as $option) {
                delete_option($option);
            }
            
            // Clean post meta for this site
            $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_trico_%'");
            
            restore_current_blog();
        }
    }
}

/**
 * Recursively delete directory
 */
function trico_recursive_delete($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
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
    
    rmdir($dir);
}

// Run cleanup
trico_uninstall_cleanup();
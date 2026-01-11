<?php
/*
Plugin Name: Synavy Hosting System
Description: Cookie fix, admin pages, multisite management
Version: 2.2
*/

defined('ABSPATH') || exit;

// ============================================================
// COOKIE DOMAIN FIX
// ============================================================
if (isset($_SERVER['HTTP_X_FORWARDED_HOST']) && !empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $forwarded_host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    $_SERVER['HTTP_HOST'] = $forwarded_host;
    
    $parts = explode('.', $forwarded_host);
    if (count($parts) >= 2) {
        $root_domain = '.' . implode('.', array_slice($parts, -2));
    } else {
        $root_domain = '.' . $forwarded_host;
    }
    
    if (!defined('COOKIE_DOMAIN')) define('COOKIE_DOMAIN', $root_domain);
    if (!defined('ADMIN_COOKIE_PATH')) define('ADMIN_COOKIE_PATH', '/');
    if (!defined('COOKIEPATH')) define('COOKIEPATH', '/');
    if (!defined('SITECOOKIEPATH')) define('SITECOOKIEPATH', '/');
}

// ============================================================
// HIDE SYSTEM PLUGINS
// ============================================================
add_filter('all_plugins', function($plugins) {
    if (!current_user_can('manage_network')) {
        $hidden = array('simply-static/simply-static.php');
        foreach ($hidden as $hide) {
            if (isset($plugins[$hide])) unset($plugins[$hide]);
        }
    }
    return $plugins;
});

// ============================================================
// SECURITY
// ============================================================
if (!defined('DISALLOW_FILE_EDIT')) define('DISALLOW_FILE_EDIT', true);
if (!defined('WP_POST_REVISIONS')) define('WP_POST_REVISIONS', 5);

// ============================================================
// CUSTOM ADMIN PAGES
// ============================================================
add_action('network_admin_menu', function() {
    add_menu_page(
        'Synavy Sites',
        'Synavy Sites',
        'manage_network',
        'synavy-sites',
        'synavy_render_sites_page',
        'dashicons-admin-multisite',
        2
    );
    
    add_menu_page(
        'Synavy Users',
        'Synavy Users',
        'manage_network',
        'synavy-users',
        'synavy_render_users_page',
        'dashicons-admin-users',
        3
    );
});

function synavy_render_sites_page() {
    global $wpdb;
    $sites = $wpdb->get_results("SELECT * FROM {$wpdb->blogs} WHERE site_id = 1 ORDER BY blog_id ASC");
    
    echo '<div class="wrap"><h1>Synavy Sites Manager</h1>';
    echo '<p>Total sites: ' . count($sites) . '</p>';
    echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
    echo '<th>ID</th><th>Domain</th><th>Path</th><th>Registered</th><th>Actions</th>';
    echo '</tr></thead><tbody>';
    
    foreach ($sites as $site) {
        $view_url = 'https://' . $site->domain . $site->path;
        $admin_url = $view_url . 'wp-admin/';
        $edit_url = network_admin_url('site-info.php?id=' . $site->blog_id);
        
        echo '<tr>';
        echo '<td>' . esc_html($site->blog_id) . '</td>';
        echo '<td>' . esc_html($site->domain) . '</td>';
        echo '<td>' . esc_html($site->path) . '</td>';
        echo '<td>' . esc_html($site->registered) . '</td>';
        echo '<td>';
        echo '<a href="' . esc_url($view_url) . '" target="_blank">View</a> | ';
        echo '<a href="' . esc_url($admin_url) . '" target="_blank">Admin</a> | ';
        echo '<a href="' . esc_url($edit_url) . '">Edit</a>';
        echo '</td></tr>';
    }
    echo '</tbody></table></div>';
}

function synavy_render_users_page() {
    global $wpdb;
    $users = $wpdb->get_results("SELECT ID, user_login, user_email, user_registered FROM {$wpdb->users} ORDER BY ID ASC");
    
    echo '<div class="wrap"><h1>Synavy Users Manager</h1>';
    echo '<p>Total users: ' . count($users) . '</p>';
    echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
    echo '<th>ID</th><th>Username</th><th>Email</th><th>Registered</th><th>Actions</th>';
    echo '</tr></thead><tbody>';
    
    foreach ($users as $user) {
        $edit_url = network_admin_url('user-edit.php?user_id=' . $user->ID);
        echo '<tr>';
        echo '<td>' . esc_html($user->ID) . '</td>';
        echo '<td>' . esc_html($user->user_login) . '</td>';
        echo '<td>' . esc_html($user->user_email) . '</td>';
        echo '<td>' . esc_html($user->user_registered) . '</td>';
        echo '<td><a href="' . esc_url($edit_url) . '">Edit</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

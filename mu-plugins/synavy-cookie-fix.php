<?php
/*
Plugin Name: Synavy Hosting System
Description: Cookie fix, admin pages, multisite management
Version: 3.0
Author: Synavy Team
*/

defined('ABSPATH') || exit;

// ============================================================
// COOKIE DOMAIN FIX (untuk proxy/Cloudflare Workers)
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
// HIDE SYSTEM PLUGINS (dari non-network-admin)
// ============================================================
add_filter('all_plugins', function($plugins) {
    if (!current_user_can('manage_network')) {
        // Plugin yang di-hide dari user biasa
        $hidden = array(
            'simply-static/simply-static.php',
            // Trico tetap visible tapi manageable oleh site admin
        );
        foreach ($hidden as $hide) {
            if (isset($plugins[$hide])) {
                unset($plugins[$hide]);
            }
        }
    }
    return $plugins;
});

// ============================================================
// SECURITY SETTINGS
// Catatan: Beberapa sudah di wp-config, ini sebagai fallback
// ============================================================
if (!defined('DISALLOW_FILE_EDIT')) define('DISALLOW_FILE_EDIT', true);
if (!defined('WP_POST_REVISIONS')) define('WP_POST_REVISIONS', 5);

// ============================================================
// CUSTOM ADMIN PAGES - SYNAVY SITES MANAGER
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

/**
 * Render Sites Manager Page
 */
function synavy_render_sites_page() {
    global $wpdb;
    
    // Get all sites
    $sites = $wpdb->get_results(
        "SELECT * FROM {$wpdb->blogs} WHERE site_id = 1 ORDER BY blog_id ASC"
    );
    
    // Get Trico stats per site
    $trico_table = $wpdb->base_prefix . 'trico_projects';
    $trico_exists = $wpdb->get_var("SHOW TABLES LIKE '{$trico_table}'") === $trico_table;
    
    ?>
    <div class="wrap">
        <h1>🌐 Synavy Sites Manager</h1>
        <p>Total sites: <strong><?php echo count($sites); ?></strong></p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Domain</th>
                    <th>Path</th>
                    <th>Registered</th>
                    <?php if ($trico_exists): ?>
                    <th style="width:100px;">Trico Projects</th>
                    <?php endif; ?>
                    <th style="width:200px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sites as $site): 
                    $view_url = 'https://' . $site->domain . $site->path;
                    $admin_url = $view_url . 'wp-admin/';
                    $trico_url = $view_url . 'wp-admin/admin.php?page=trico-dashboard';
                    $edit_url = network_admin_url('site-info.php?id=' . $site->blog_id);
                    
                    // Count Trico projects
                    $project_count = 0;
                    if ($trico_exists) {
                        $project_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$trico_table} WHERE site_id = %d",
                            $site->blog_id
                        ));
                    }
                ?>
                <tr>
                    <td><?php echo esc_html($site->blog_id); ?></td>
                    <td><strong><?php echo esc_html($site->domain); ?></strong></td>
                    <td><code><?php echo esc_html($site->path); ?></code></td>
                    <td><?php echo esc_html(date('Y-m-d', strtotime($site->registered))); ?></td>
                    <?php if ($trico_exists): ?>
                    <td>
                        <?php if ($project_count > 0): ?>
                        <span style="background:#10b981;color:#fff;padding:2px 8px;border-radius:10px;font-size:12px;">
                            <?php echo $project_count; ?> projects
                        </span>
                        <?php else: ?>
                        <span style="color:#999;">0</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td>
                        <a href="<?php echo esc_url($view_url); ?>" target="_blank" class="button button-small">View</a>
                        <a href="<?php echo esc_url($admin_url); ?>" target="_blank" class="button button-small">Admin</a>
                        <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">Edit</a>
                        <?php if ($trico_exists): ?>
                        <a href="<?php echo esc_url($trico_url); ?>" target="_blank" class="button button-small button-primary">Trico</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top:20px;padding:15px;background:#f0f0f1;border-radius:5px;">
            <h3 style="margin-top:0;">💡 Quick Info</h3>
            <ul style="margin:0;">
                <li><strong>Trico AI Assistant:</strong> <?php echo is_plugin_active('trico-ai-assistant/trico-ai-assistant.php') ? '✅ Active' : '❌ Not Active'; ?></li>
                <li><strong>Trico Theme:</strong> <?php echo wp_get_theme()->get('Name') === 'Trico Theme' ? '✅ Active' : '⚠️ Not Active on main site'; ?></li>
                <li><strong>API Keys:</strong> <?php 
                    $key_count = 0;
                    for ($i = 1; $i <= 15; $i++) {
                        if (!empty(getenv('GROQ_KEY_' . $i))) $key_count++;
                    }
                    echo $key_count . ' Groq keys configured';
                ?></li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Render Users Manager Page
 */
function synavy_render_users_page() {
    global $wpdb;
    
    $users = $wpdb->get_results(
        "SELECT u.ID, u.user_login, u.user_email, u.user_registered,
                (SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE user_id = u.ID AND meta_key LIKE '%capabilities%' AND meta_value LIKE '%administrator%') as is_admin
         FROM {$wpdb->users} u 
         ORDER BY u.ID ASC"
    );
    
    ?>
    <div class="wrap">
        <h1>👥 Synavy Users Manager</h1>
        <p>Total users: <strong><?php echo count($users); ?></strong></p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th style="width:100px;">Role</th>
                    <th style="width:100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): 
                    $edit_url = network_admin_url('user-edit.php?user_id=' . $user->ID);
                ?>
                <tr>
                    <td><?php echo esc_html($user->ID); ?></td>
                    <td><strong><?php echo esc_html($user->user_login); ?></strong></td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td><?php echo esc_html(date('Y-m-d', strtotime($user->user_registered))); ?></td>
                    <td>
                        <?php if ($user->is_admin > 0): ?>
                        <span style="background:#6366f1;color:#fff;padding:2px 8px;border-radius:10px;font-size:12px;">Admin</span>
                        <?php else: ?>
                        <span style="color:#999;">User</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ============================================================
// HELPER: Check if plugin is active (for mu-plugin context)
// ============================================================
if (!function_exists('is_plugin_active')) {
    function is_plugin_active($plugin) {
        $active_plugins = get_option('active_plugins', array());
        if (is_multisite()) {
            $network_plugins = get_site_option('active_sitewide_plugins', array());
            return in_array($plugin, $active_plugins) || isset($network_plugins[$plugin]);
        }
        return in_array($plugin, $active_plugins);
    }
}
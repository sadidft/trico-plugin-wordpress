<?php
/**
 * Network Dashboard View
 * 
 * @package Trico_AI_Assistant
 */

defined('ABSPATH') || exit;
?>

<div class="wrap trico-wrap">
    <div class="trico-header">
        <h1>
            <span class="trico-logo">🌐</span>
            <?php _e('Trico Network Overview', 'trico-ai'); ?>
        </h1>
        <p class="trico-tagline"><?php _e('Manage Trico across all network sites', 'trico-ai'); ?></p>
    </div>
    
    <!-- Network Stats -->
    <div class="trico-status-grid">
        <div class="trico-card trico-card-stat">
            <div class="trico-stat-icon">🌐</div>
            <div class="trico-stat-content">
                <span class="trico-stat-value"><?php echo count($sites); ?></span>
                <span class="trico-stat-label"><?php _e('Network Sites', 'trico-ai'); ?></span>
            </div>
        </div>
        
        <div class="trico-card trico-card-stat">
            <div class="trico-stat-icon">📁</div>
            <div class="trico-stat-content">
                <span class="trico-stat-value"><?php echo esc_html($total_projects); ?></span>
                <span class="trico-stat-label"><?php _e('Total Projects', 'trico-ai'); ?></span>
            </div>
        </div>
        
        <div class="trico-card trico-card-stat">
            <div class="trico-stat-icon">🔑</div>
            <div class="trico-stat-content">
                <span class="trico-stat-value"><?php echo trico()->api_manager->get_key_count(); ?></span>
                <span class="trico-stat-label"><?php _e('API Keys', 'trico-ai'); ?></span>
            </div>
        </div>
        
        <div class="trico-card trico-card-stat">
            <div class="trico-stat-icon">⚡</div>
            <div class="trico-stat-content">
                <?php $usage = trico()->api_manager->get_usage_stats(); ?>
                <span class="trico-stat-value"><?php echo esc_html($usage['requests_today']); ?></span>
                <span class="trico-stat-label"><?php _e('Requests Today', 'trico-ai'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Sites List -->
    <div class="trico-section">
        <h2><?php _e('Sites Overview', 'trico-ai'); ?></h2>
        <div class="trico-card">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Site', 'trico-ai'); ?></th>
                        <th style="width: 120px;"><?php _e('Projects', 'trico-ai'); ?></th>
                        <th style="width: 120px;"><?php _e('Status', 'trico-ai'); ?></th>
                        <th style="width: 150px;"><?php _e('Actions', 'trico-ai'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sites as $site): ?>
                    <?php
                    switch_to_blog($site->blog_id);
                    $site_projects = trico()->database->get_project_count($site->blog_id);
                    $site_url = get_site_url();
                    $admin_url = admin_url('admin.php?page=trico-dashboard');
                    restore_current_blog();
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($site->domain . $site->path); ?></strong>
                            <br><small><?php echo esc_html($site_url); ?></small>
                        </td>
                        <td>
                            <span class="trico-badge trico-badge-secondary">
                                <?php echo esc_html($site_projects); ?> <?php _e('projects', 'trico-ai'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!$site->deleted && !$site->archived): ?>
                                <span class="trico-badge trico-badge-success"><?php _e('Active', 'trico-ai'); ?></span>
                            <?php else: ?>
                                <span class="trico-badge trico-badge-warning"><?php _e('Inactive', 'trico-ai'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($admin_url); ?>" class="button button-small">
                                <?php _e('View Dashboard', 'trico-ai'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="trico-section">
        <h2><?php _e('Quick Links', 'trico-ai'); ?></h2>
        <div class="trico-actions-grid">
            <a href="<?php echo network_admin_url('admin.php?page=trico-api-status'); ?>" class="trico-action-card">
                <span class="trico-action-icon">🔑</span>
                <span class="trico-action-title"><?php _e('API Status', 'trico-ai'); ?></span>
                <span class="trico-action-desc"><?php _e('Manage API keys and check status', 'trico-ai'); ?></span>
            </a>
            
            <a href="<?php echo network_admin_url('settings.php'); ?>" class="trico-action-card">
                <span class="trico-action-icon">⚙️</span>
                <span class="trico-action-title"><?php _e('Network Settings', 'trico-ai'); ?></span>
                <span class="trico-action-desc"><?php _e('Configure network-wide settings', 'trico-ai'); ?></span>
            </a>
        </div>
    </div>
</div>
<?php defined('ABSPATH') || exit; ?>

<div class="wrap trico-wrap">
    <div class="trico-header">
        <h1>
            <span class="trico-logo">⚡</span>
            <?php _e('Trico AI Assistant', 'trico-ai'); ?>
        </h1>
        <p class="trico-tagline"><?php _e('Generate stunning websites with AI', 'trico-ai'); ?></p>
    </div>
    
    <!-- Status Cards -->
    <div class="trico-status-grid">
        <div class="trico-card trico-card-stat">
            <div class="trico-stat-icon">🚀</div>
            <div class="trico-stat-content">
                <span class="trico-stat-value"><?php echo esc_html($stats['total_projects']); ?></span>
                <span class="trico-stat-label"><?php _e('Total Projects', 'trico-ai'); ?></span>
            </div>
        </div>
        
        <div class="trico-card trico-card-stat">
            <div class="trico-stat-icon">🌐</div>
            <div class="trico-stat-content">
                <span class="trico-stat-value"><?php echo esc_html($stats['deployed']); ?></span>
                <span class="trico-stat-label"><?php _e('Deployed', 'trico-ai'); ?></span>
            </div>
        </div>
        
        <div class="trico-card trico-card-stat">
            <div class="trico-stat-icon">🔑</div>
            <div class="trico-stat-content">
                <span class="trico-stat-value"><?php echo esc_html($api_usage['active_keys']); ?>/<?php echo esc_html($api_usage['total_keys']); ?></span>
                <span class="trico-stat-label"><?php _e('API Keys Active', 'trico-ai'); ?></span>
            </div>
        </div>
        
        <div class="trico-card trico-card-stat">
            <div class="trico-stat-icon">⚡</div>
            <div class="trico-stat-content">
                <span class="trico-stat-value"><?php echo esc_html($api_usage['requests_today']); ?></span>
                <span class="trico-stat-label"><?php _e('Requests Today', 'trico-ai'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="trico-section">
        <h2><?php _e('Quick Actions', 'trico-ai'); ?></h2>
        <div class="trico-actions-grid">
            <a href="<?php echo admin_url('admin.php?page=trico-generator'); ?>" class="trico-action-card trico-action-primary">
                <span class="trico-action-icon">✨</span>
                <span class="trico-action-title"><?php _e('Generate New Website', 'trico-ai'); ?></span>
                <span class="trico-action-desc"><?php _e('Create a stunning website with AI', 'trico-ai'); ?></span>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=trico-projects'); ?>" class="trico-action-card">
                <span class="trico-action-icon">📁</span>
                <span class="trico-action-title"><?php _e('View Projects', 'trico-ai'); ?></span>
                <span class="trico-action-desc"><?php _e('Manage your generated websites', 'trico-ai'); ?></span>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=trico-synalytics'); ?>" class="trico-action-card">
                <span class="trico-action-icon">📊</span>
                <span class="trico-action-title"><?php _e('Synalytics', 'trico-ai'); ?></span>
                <span class="trico-action-desc"><?php _e('View your website analytics', 'trico-ai'); ?></span>
            </a>
        </div>
    </div>
    
    <!-- Environment Status -->
    <div class="trico-section">
        <h2><?php _e('Environment Status', 'trico-ai'); ?></h2>
        <div class="trico-card">
            <table class="trico-status-table">
                <tr>
                    <td><strong><?php _e('Groq API Keys', 'trico-ai'); ?></strong></td>
                    <td>
                        <?php if ($api_status['groq_keys'] > 0): ?>
                            <span class="trico-badge trico-badge-success">
                                <?php printf(__('%d keys configured', 'trico-ai'), $api_status['groq_keys']); ?>
                            </span>
                        <?php else: ?>
                            <span class="trico-badge trico-badge-error">
                                <?php _e('Not configured', 'trico-ai'); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('Cloudflare', 'trico-ai'); ?></strong></td>
                    <td>
                        <?php if ($api_status['cloudflare']): ?>
                            <span class="trico-badge trico-badge-success"><?php _e('Connected', 'trico-ai'); ?></span>
                        <?php else: ?>
                            <span class="trico-badge trico-badge-warning"><?php _e('Not configured', 'trico-ai'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('B2 Storage', 'trico-ai'); ?></strong></td>
                    <td>
                        <?php if ($api_status['b2_storage']): ?>
                            <span class="trico-badge trico-badge-success"><?php _e('Connected', 'trico-ai'); ?></span>
                        <?php else: ?>
                            <span class="trico-badge trico-badge-warning"><?php _e('Not configured', 'trico-ai'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('Domain', 'trico-ai'); ?></strong></td>
                    <td><code><?php echo esc_html($api_status['domain']); ?></code></td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- Recent Projects -->
    <?php if (!empty($recent_projects)): ?>
    <div class="trico-section">
        <h2><?php _e('Recent Projects', 'trico-ai'); ?></h2>
        <div class="trico-card">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'trico-ai'); ?></th>
                        <th><?php _e('Status', 'trico-ai'); ?></th>
                        <th><?php _e('Author', 'trico-ai'); ?></th>
                        <th><?php _e('Updated', 'trico-ai'); ?></th>
                        <th><?php _e('Actions', 'trico-ai'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_projects as $project): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($project['name']); ?></strong>
                            <?php if (!empty($project['subdomain'])): ?>
                                <br><small><code><?php echo esc_html($project['subdomain']); ?>.<?php echo esc_html(TRICO_DEFAULT_DOMAIN); ?></code></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($project['status'] === 'published'): ?>
                                <span class="trico-badge trico-badge-success"><?php _e('Live', 'trico-ai'); ?></span>
                            <?php else: ?>
                                <span class="trico-badge trico-badge-secondary"><?php _e('Draft', 'trico-ai'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($project['author_name'] ?? 'Unknown'); ?></td>
                        <td><?php echo esc_html(human_time_diff(strtotime($project['updated_at']))); ?> ago</td>
                        <td>
                            <?php if (!empty($project['post_id'])): ?>
                                <a href="<?php echo get_edit_post_link($project['post_id']); ?>" class="button button-small">
                                    <?php _e('Edit', 'trico-ai'); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($project['status'] === 'published' && !empty($project['cf_deployment_url'])): ?>
                                <a href="<?php echo esc_url($project['cf_deployment_url']); ?>" target="_blank" class="button button-small">
                                    <?php _e('View Live', 'trico-ai'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <div class="trico-footer">
        <p>
            <strong>Trico AI Assistant</strong> v<?php echo TRICO_VERSION; ?> | 
            <?php _e('Made with ❤️ by Synavy Team', 'trico-ai'); ?>
        </p>
    </div>
</div>
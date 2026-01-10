<?php
/**
 * Deploy Settings View
 * Domain and deployment configuration
 * 
 * @package Trico_AI_Assistant
 */

defined('ABSPATH') || exit;

// Handle form submission
if (isset($_POST['trico_save_deploy_settings']) && check_admin_referer('trico_deploy_settings')) {
    $subdomain = sanitize_key($_POST['trico_subdomain'] ?? '');
    $custom_domain = sanitize_text_field($_POST['trico_custom_domain'] ?? '');
    $project_id = intval($_POST['project_id'] ?? 0);
    
    if ($project_id) {
        trico()->database->update_project($project_id, array(
            'subdomain' => $subdomain,
            'custom_domain' => $custom_domain
        ));
        
        $success_message = __('Settings saved successfully!', 'trico-ai');
    }
}

// Get project if specified
$project_id = intval($_GET['project_id'] ?? 0);
$project = $project_id ? trico()->database->get_project($project_id) : null;

// Get Cloudflare status
require_once TRICO_PLUGIN_DIR . 'includes/class-trico-cloudflare.php';
$cloudflare = new Trico_Cloudflare();
$cf_status = $cloudflare->get_status();
?>

<div class="wrap trico-wrap">
    <div class="trico-header">
        <h1>
            <span class="trico-logo">🌐</span>
            <?php _e('Deploy Settings', 'trico-ai'); ?>
        </h1>
        <p class="trico-tagline"><?php _e('Configure domain and deployment settings', 'trico-ai'); ?></p>
    </div>
    
    <?php if (!empty($success_message)): ?>
    <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($success_message); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Cloudflare Status -->
    <div class="trico-card">
        <h2><?php _e('Cloudflare Connection', 'trico-ai'); ?></h2>
        <table class="trico-status-table">
            <tr>
                <td><strong><?php _e('Status', 'trico-ai'); ?></strong></td>
                <td>
                    <?php if ($cf_status['connected']): ?>
                        <span class="trico-badge trico-badge-success"><?php _e('Connected', 'trico-ai'); ?></span>
                    <?php else: ?>
                        <span class="trico-badge trico-badge-error"><?php _e('Not Connected', 'trico-ai'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong><?php _e('Default Domain', 'trico-ai'); ?></strong></td>
                <td><code><?php echo esc_html(TRICO_DEFAULT_DOMAIN); ?></code></td>
            </tr>
            <?php if (!$cf_status['configured']): ?>
            <tr>
                <td colspan="2">
                    <p class="description">
                        <?php _e('Add CF_API_TOKEN and CF_ACCOUNT_ID to HF Secrets to enable deployment.', 'trico-ai'); ?>
                    </p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <?php if ($project): ?>
    <!-- Project Domain Settings -->
    <div class="trico-card">
        <h2><?php printf(__('Domain Settings: %s', 'trico-ai'), esc_html($project['name'])); ?></h2>
        
        <form method="post">
            <?php wp_nonce_field('trico_deploy_settings'); ?>
            <input type="hidden" name="project_id" value="<?php echo esc_attr($project_id); ?>">
            
            <div class="trico-form-group">
                <label class="trico-form-label" for="trico_subdomain">
                    <?php _e('Subdomain', 'trico-ai'); ?>
                </label>
                <div class="trico-input-group">
                    <input 
                        type="text" 
                        id="trico_subdomain" 
                        name="trico_subdomain" 
                        class="trico-input"
                        value="<?php echo esc_attr($project['subdomain'] ?? ''); ?>"
                        placeholder="mysite"
                    >
                    <span class="trico-input-suffix">.<?php echo esc_html(TRICO_DEFAULT_DOMAIN); ?></span>
                </div>
                <p class="trico-form-hint">
                    <?php _e('Your site will be available at: ', 'trico-ai'); ?>
                    <code id="preview-url"><?php echo esc_html(($project['subdomain'] ?? 'mysite') . '.' . TRICO_DEFAULT_DOMAIN); ?></code>
                </p>
            </div>
            
            <div class="trico-form-group">
                <label class="trico-form-label" for="trico_custom_domain">
                    <?php _e('Custom Domain (Optional)', 'trico-ai'); ?>
                </label>
                <input 
                    type="text" 
                    id="trico_custom_domain" 
                    name="trico_custom_domain" 
                    class="trico-input"
                    value="<?php echo esc_attr($project['custom_domain'] ?? ''); ?>"
                    placeholder="www.example.com"
                >
                <p class="trico-form-hint">
                    <?php _e('If you want to use your own domain, enter it here.', 'trico-ai'); ?>
                </p>
            </div>
            
            <?php if (!empty($project['custom_domain'])): ?>
            <div class="trico-card trico-card-info">
                <h4><?php _e('DNS Configuration Required', 'trico-ai'); ?></h4>
                <p><?php _e('Add this CNAME record to your domain DNS:', 'trico-ai'); ?></p>
                <table class="trico-dns-table">
                    <tr>
                        <th><?php _e('Type', 'trico-ai'); ?></th>
                        <th><?php _e('Name', 'trico-ai'); ?></th>
                        <th><?php _e('Target', 'trico-ai'); ?></th>
                    </tr>
                    <tr>
                        <td><code>CNAME</code></td>
                        <td><code><?php echo esc_html($project['custom_domain']); ?></code></td>
                        <td><code><?php echo esc_html($project['cf_project_name'] ?? $project['slug']); ?>.pages.dev</code></td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="trico-form-actions">
                <button type="submit" name="trico_save_deploy_settings" class="trico-btn trico-btn-primary">
                    💾 <?php _e('Save Settings', 'trico-ai'); ?>
                </button>
                
                <?php if ($cf_status['connected']): ?>
                <button type="button" class="trico-btn trico-btn-secondary trico-deploy-btn" data-project-id="<?php echo esc_attr($project_id); ?>">
                    🚀 <?php _e('Deploy Now', 'trico-ai'); ?>
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Deployment History -->
    <?php if (!empty($project['cf_project_name'])): ?>
    <div class="trico-card">
        <h2><?php _e('Deployment History', 'trico-ai'); ?></h2>
        
        <?php
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-deployer.php';
        $deployer = new Trico_Deployer();
        $history = $deployer->get_deployment_history($project_id);
        ?>
        
        <?php if (!empty($history)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Deployment ID', 'trico-ai'); ?></th>
                    <th><?php _e('URL', 'trico-ai'); ?></th>
                    <th><?php _e('Date', 'trico-ai'); ?></th>
                    <th><?php _e('Status', 'trico-ai'); ?></th>
                    <th><?php _e('Actions', 'trico-ai'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $deploy): ?>
                <tr>
                    <td><code><?php echo esc_html(substr($deploy['id'], 0, 8)); ?>...</code></td>
                    <td>
                        <a href="<?php echo esc_url($deploy['url']); ?>" target="_blank">
                            <?php echo esc_html($deploy['url']); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html(date('Y-m-d H:i', strtotime($deploy['created_at']))); ?></td>
                    <td>
                        <?php if ($deploy['is_current']): ?>
                            <span class="trico-badge trico-badge-success"><?php _e('Current', 'trico-ai'); ?></span>
                        <?php else: ?>
                            <span class="trico-badge trico-badge-secondary"><?php echo esc_html($deploy['status']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$deploy['is_current']): ?>
                        <button type="button" class="button trico-rollback-btn" data-project-id="<?php echo esc_attr($project_id); ?>" data-deployment-id="<?php echo esc_attr($deploy['id']); ?>">
                            <?php _e('Rollback', 'trico-ai'); ?>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="trico-no-data"><?php _e('No deployments yet.', 'trico-ai'); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    
    <!-- No project selected -->
    <div class="trico-card">
        <h2><?php _e('Select a Project', 'trico-ai'); ?></h2>
        <p><?php _e('Choose a project from the list below to configure its deployment settings.', 'trico-ai'); ?></p>
        
        <?php
        $projects = trico()->database->get_all_projects();
        ?>
        
        <?php if (!empty($projects)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Project', 'trico-ai'); ?></th>
                    <th><?php _e('Status', 'trico-ai'); ?></th>
                    <th><?php _e('Domain', 'trico-ai'); ?></th>
                    <th><?php _e('Actions', 'trico-ai'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $p): ?>
                <tr>
                    <td><strong><?php echo esc_html($p['name']); ?></strong></td>
                    <td>
                        <?php if ($p['status'] === 'published'): ?>
                            <span class="trico-badge trico-badge-success"><?php _e('Live', 'trico-ai'); ?></span>
                        <?php else: ?>
                            <span class="trico-badge trico-badge-secondary"><?php _e('Draft', 'trico-ai'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($p['cf_deployment_url'])): ?>
                            <a href="<?php echo esc_url($p['cf_deployment_url']); ?>" target="_blank">
                                <?php echo esc_html(parse_url($p['cf_deployment_url'], PHP_URL_HOST)); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted"><?php _e('Not deployed', 'trico-ai'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=trico-deploy-settings&project_id=' . $p['id']); ?>" class="button">
                            <?php _e('Configure', 'trico-ai'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><?php _e('No projects found. Generate a website first!', 'trico-ai'); ?></p>
        <a href="<?php echo admin_url('admin.php?page=trico-generator'); ?>" class="trico-btn trico-btn-primary">
            <?php _e('Generate Website', 'trico-ai'); ?>
        </a>
        <?php endif; ?>
    </div>
    
    <?php endif; ?>
</div>

<style>
.trico-input-group {
    display: flex;
    align-items: center;
}

.trico-input-group .trico-input {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    flex: 1;
}

.trico-input-suffix {
    background: var(--trico-light);
    border: 1px solid var(--trico-border);
    border-left: none;
    padding: 0.75rem 1rem;
    border-radius: 0 0.5rem 0.5rem 0;
    color: var(--trico-gray);
    font-family: monospace;
}

.trico-card-info {
    background: #dbeafe;
    border-color: #3b82f6;
}

.trico-dns-table {
    width: 100%;
    margin-top: 0.5rem;
    border-collapse: collapse;
}

.trico-dns-table th,
.trico-dns-table td {
    padding: 0.5rem;
    border: 1px solid rgba(0,0,0,0.1);
    text-align: left;
}

.trico-dns-table th {
    background: rgba(0,0,0,0.05);
}

.text-muted {
    color: var(--trico-gray);
}
</style>

<script>
jQuery(document).ready(function($) {
    // Preview URL update
    $('#trico_subdomain').on('input', function() {
        var subdomain = $(this).val() || 'mysite';
        $('#preview-url').text(subdomain + '.<?php echo esc_js(TRICO_DEFAULT_DOMAIN); ?>');
    });
    
    // Rollback button
    $('.trico-rollback-btn').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to rollback to this deployment?', 'trico-ai'); ?>')) {
            return;
        }
        
        var $btn = $(this);
        var projectId = $btn.data('project-id');
        var deploymentId = $btn.data('deployment-id');
        
        $btn.prop('disabled', true).text('<?php _e('Rolling back...', 'trico-ai'); ?>');
        
        $.ajax({
            url: tricoAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'trico_rollback',
                nonce: tricoAdmin.nonce,
                project_id: projectId,
                deployment_id: deploymentId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Rollback failed');
                    $btn.prop('disabled', false).text('<?php _e('Rollback', 'trico-ai'); ?>');
                }
            }
        });
    });
});
</script>
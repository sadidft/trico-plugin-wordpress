<?php
/**
 * Settings Page View
 * 
 * @package Trico_AI_Assistant
 */

defined('ABSPATH') || exit;

settings_errors('trico');
?>

<div class="wrap trico-wrap">
    <div class="trico-header">
        <h1>
            <span class="trico-logo">⚙️</span>
            <?php _e('Trico Settings', 'trico-ai'); ?>
        </h1>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('trico_settings'); ?>
        
        <!-- Domain Settings -->
        <div class="trico-card trico-settings-section">
            <h2><?php _e('Domain Settings', 'trico-ai'); ?></h2>
            
            <div class="trico-form-group">
                <label class="trico-form-label" for="trico_custom_domain">
                    <?php _e('Custom Domain', 'trico-ai'); ?>
                </label>
                <input 
                    type="text" 
                    id="trico_custom_domain" 
                    name="trico_custom_domain" 
                    class="trico-input"
                    value="<?php echo esc_attr(get_option('trico_custom_domain', '')); ?>"
                    placeholder="<?php echo esc_attr(TRICO_DEFAULT_DOMAIN); ?>"
                >
                <p class="trico-form-hint">
                    <?php printf(__('Leave empty to use default: %s', 'trico-ai'), '<code>' . TRICO_DEFAULT_DOMAIN . '</code>'); ?>
                </p>
            </div>
            
            <div class="trico-form-group">
                <label class="trico-form-label" for="trico_subdomain_pattern">
                    <?php _e('URL Pattern', 'trico-ai'); ?>
                </label>
                <input 
                    type="text" 
                    id="trico_subdomain_pattern" 
                    name="trico_subdomain_pattern" 
                    class="trico-input"
                    value="<?php echo esc_attr(get_option('trico_subdomain_pattern', '{project}.{domain}')); ?>"
                >
                <p class="trico-form-hint">
                    <?php _e('Available variables: {project}, {domain}, {user}', 'trico-ai'); ?>
                    <br>
                    <?php _e('Example: {project}.{domain} → mysite.synpages.synavy.com', 'trico-ai'); ?>
                </p>
            </div>
        </div>
        
        <!-- Default Settings -->
        <div class="trico-card trico-settings-section">
            <h2><?php _e('Default Settings', 'trico-ai'); ?></h2>
            
            <div class="trico-form-group">
                <label class="trico-form-label" for="trico_default_framework">
                    <?php _e('Default CSS Framework', 'trico-ai'); ?>
                </label>
                <select id="trico_default_framework" name="trico_default_framework" class="trico-select">
                    <?php 
                    $current_framework = get_option('trico_default_framework', 'tailwind');
                    foreach ($frameworks as $key => $framework): 
                    ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($key, $current_framework); ?>>
                        <?php echo esc_html($framework['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="trico-form-group">
                <label class="trico-checkbox-label">
                    <input 
                        type="checkbox" 
                        name="trico_analytics_default" 
                        value="1"
                        <?php checked(get_option('trico_analytics_default', 1)); ?>
                    >
                    <span><?php _e('Enable Cloudflare Analytics by default', 'trico-ai'); ?></span>
                </label>
            </div>
            
            <div class="trico-form-group">
                <label class="trico-checkbox-label">
                    <input 
                        type="checkbox" 
                        name="trico_pwa_default" 
                        value="1"
                        <?php checked(get_option('trico_pwa_default', 0)); ?>
                    >
                    <span><?php _e('Enable PWA features by default', 'trico-ai'); ?></span>
                </label>
            </div>
        </div>
        
        <!-- Environment Status -->
        <div class="trico-card trico-settings-section">
            <h2><?php _e('Environment Status', 'trico-ai'); ?></h2>
            <p class="trico-form-hint">
                <?php _e('These settings are configured via HF Spaces Secrets and cannot be changed here.', 'trico-ai'); ?>
            </p>
            
            <table class="trico-status-table">
                <tr>
                    <td><strong>TRICO_DOMAIN</strong></td>
                    <td><code><?php echo esc_html(TRICO_DEFAULT_DOMAIN ?: 'Not set'); ?></code></td>
                </tr>
                <tr>
                    <td><strong>CF_API_TOKEN</strong></td>
                    <td>
                        <?php if (TRICO_CF_API_TOKEN): ?>
                            <span class="trico-badge trico-badge-success"><?php _e('Configured', 'trico-ai'); ?></span>
                        <?php else: ?>
                            <span class="trico-badge trico-badge-warning"><?php _e('Not set', 'trico-ai'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>CF_ACCOUNT_ID</strong></td>
                    <td>
                        <?php if (TRICO_CF_ACCOUNT_ID): ?>
                            <span class="trico-badge trico-badge-success"><?php _e('Configured', 'trico-ai'); ?></span>
                        <?php else: ?>
                            <span class="trico-badge trico-badge-warning"><?php _e('Not set', 'trico-ai'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>B2 Storage</strong></td>
                    <td>
                        <?php if (TRICO_B2_KEY_ID && TRICO_B2_APP_KEY): ?>
                            <span class="trico-badge trico-badge-success"><?php _e('Configured', 'trico-ai'); ?></span>
                        <?php else: ?>
                            <span class="trico-badge trico-badge-warning"><?php _e('Not set', 'trico-ai'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Groq API Keys</strong></td>
                    <td>
                        <?php 
                        $key_count = trico()->api_manager->get_key_count();
                        if ($key_count > 0): 
                        ?>
                            <span class="trico-badge trico-badge-success">
                                <?php printf(__('%d keys', 'trico-ai'), $key_count); ?>
                            </span>
                        <?php else: ?>
                            <span class="trico-badge trico-badge-error"><?php _e('No keys configured', 'trico-ai'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="trico-form-actions">
            <button type="submit" name="trico_save_settings" class="trico-btn trico-btn-primary">
                💾 <?php _e('Save Settings', 'trico-ai'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.trico-settings-section {
    margin-bottom: 1.5rem;
}

.trico-settings-section h2 {
    margin: 0 0 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--trico-border);
    font-size: 1.125rem;
}

.trico-form-actions {
    margin-top: 2rem;
}
</style>
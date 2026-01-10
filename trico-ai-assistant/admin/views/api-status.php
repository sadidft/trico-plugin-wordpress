<?php
/**
 * API Status Page View
 * 
 * @package Trico_AI_Assistant
 */

defined('ABSPATH') || exit;
?>

<div class="wrap trico-wrap">
    <div class="trico-header">
        <h1>
            <span class="trico-logo">🔑</span>
            <?php _e('API Status', 'trico-ai'); ?>
        </h1>
        <p class="trico-tagline"><?php _e('Monitor your API keys and usage', 'trico-ai'); ?></p>
    </div>
    
    <!-- Usage Summary -->
    <div class="trico-status-grid">
        <div class="trico-card trico-card-stat">
            <div class="trico-stat-icon">🔑</div>
            <div class="trico-stat-content">
                <span class="trico-stat-value"><?php echo esc_html($usage_stats['total_keys']); ?></span>
                <span class="trico-stat-label"><?php _e('Total API Keys', 'trico-ai'); ?></span>
            </div>
        </div>
        
        <div class="trico-card trico-card-stat">
            <div class="trico-stat-icon">✅</div>
            <div class="trico-stat-content">
                <span class="trico-stat-value"><?php echo esc_html($usage_stats['active_keys']); ?></span>
                <span class="trico-stat-label"><?php _e('Active Keys', 'trico-ai'); ?></span>
            </div>
        </div>
        
        <div class="trico-card trico-card-stat">
            <div class="trico-stat-icon">⏸️</div>
            <div class="trico-stat-content">
                <span class="trico-stat-value"><?php echo esc_html($usage_stats['limited_keys']); ?></span>
                <span class="trico-stat-label"><?php _e('Rate Limited', 'trico-ai'); ?></span>
            </div>
        </div>
        
        <div class="trico-card trico-card-stat">
            <div class="trico-stat-icon">📊</div>
            <div class="trico-stat-content">
                <span class="trico-stat-value"><?php echo esc_html($usage_stats['requests_today']); ?></span>
                <span class="trico-stat-label"><?php _e('Requests Today', 'trico-ai'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Groq API Keys -->
    <div class="trico-section">
        <h2><?php _e('Groq API Keys (Rotation Pool)', 'trico-ai'); ?></h2>
        
        <?php if (empty($keys_status)): ?>
        <div class="trico-card trico-alert trico-alert-warning">
            <p>
                <strong><?php _e('No API keys configured!', 'trico-ai'); ?></strong><br>
                <?php _e('Add GROQ_KEY_1, GROQ_KEY_2, etc. to your HF Spaces Secrets.', 'trico-ai'); ?>
            </p>
        </div>
        <?php else: ?>
        <div class="trico-card">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 80px;"><?php _e('Index', 'trico-ai'); ?></th>
                        <th><?php _e('Key Preview', 'trico-ai'); ?></th>
                        <th style="width: 100px;"><?php _e('Status', 'trico-ai'); ?></th>
                        <th style="width: 120px;"><?php _e('Requests', 'trico-ai'); ?></th>
                        <th><?php _e('Last Used', 'trico-ai'); ?></th>
                        <th style="width: 100px;"><?php _e('Actions', 'trico-ai'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($keys_status as $key): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($key['name']); ?></strong>
                        </td>
                        <td>
                            <code><?php echo esc_html($key['key_preview']); ?></code>
                        </td>
                        <td>
                            <?php if ($key['status'] === 'active'): ?>
                                <span class="trico-badge trico-badge-success"><?php _e('Active', 'trico-ai'); ?></span>
                            <?php else: ?>
                                <span class="trico-badge trico-badge-error"><?php _e('Limited', 'trico-ai'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo esc_html($key['requests_today']); ?> <?php _e('today', 'trico-ai'); ?>
                        </td>
                        <td>
                            <?php 
                            if (!empty($key['last_used'])) {
                                echo esc_html(human_time_diff(strtotime($key['last_used']))) . ' ago';
                            } else {
                                echo '—';
                            }
                            ?>
                            <?php if (!empty($key['limited_until'])): ?>
                                <br><small class="text-warning">
                                    <?php printf(__('Limited until: %s', 'trico-ai'), esc_html($key['limited_until'])); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($key['status'] === 'limited'): ?>
                            <button type="button" class="button trico-reset-key" data-key-index="<?php echo esc_attr($key['index']); ?>">
                                <?php _e('Reset', 'trico-ai'); ?>
                            </button>
                            <?php else: ?>
                            —
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Other Services -->
    <div class="trico-section">
        <h2><?php _e('Connected Services', 'trico-ai'); ?></h2>
        <div class="trico-card">
            <table class="trico-status-table">
                <tr>
                    <td><strong><?php _e('Cloudflare API', 'trico-ai'); ?></strong></td>
                    <td>
                        <?php if ($env_status['cloudflare']): ?>
                            <span class="trico-badge trico-badge-success"><?php _e('Connected', 'trico-ai'); ?></span>
                        <?php else: ?>
                            <span class="trico-badge trico-badge-warning"><?php _e('Not Configured', 'trico-ai'); ?></span>
                            <br><small><?php _e('Add CF_API_TOKEN and CF_ACCOUNT_ID to HF Secrets', 'trico-ai'); ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('Backblaze B2', 'trico-ai'); ?></strong></td>
                    <td>
                        <?php if ($env_status['b2_storage']): ?>
                            <span class="trico-badge trico-badge-success"><?php _e('Connected', 'trico-ai'); ?></span>
                        <?php else: ?>
                            <span class="trico-badge trico-badge-warning"><?php _e('Not Configured', 'trico-ai'); ?></span>
                            <br><small><?php _e('Add B2_KEY_ID, B2_APP_KEY, B2_BUCKET_ID, B2_BUCKET_NAME to HF Secrets', 'trico-ai'); ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('Pollinations.ai', 'trico-ai'); ?></strong></td>
                    <td>
                        <span class="trico-badge trico-badge-success"><?php _e('Always Available', 'trico-ai'); ?></span>
                        <br><small><?php _e('Free, unlimited, no API key required', 'trico-ai'); ?></small>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- How to Add Keys -->
    <div class="trico-section">
        <h2><?php _e('How to Add API Keys', 'trico-ai'); ?></h2>
        <div class="trico-card">
            <ol class="trico-instructions">
                <li>
                    <?php _e('Go to your Hugging Face Space Settings', 'trico-ai'); ?>
                </li>
                <li>
                    <?php _e('Navigate to "Repository secrets"', 'trico-ai'); ?>
                </li>
                <li>
                    <?php _e('Add secrets with these names:', 'trico-ai'); ?>
                    <ul>
                        <li><code>GROQ_KEY_1</code>, <code>GROQ_KEY_2</code>, ... <code>GROQ_KEY_15</code></li>
                        <li><code>CF_API_TOKEN</code>, <code>CF_ACCOUNT_ID</code></li>
                        <li><code>B2_KEY_ID</code>, <code>B2_APP_KEY</code>, <code>B2_BUCKET_ID</code>, <code>B2_BUCKET_NAME</code></li>
                    </ul>
                </li>
                <li>
                    <?php _e('Restart your Space to apply changes', 'trico-ai'); ?>
                </li>
            </ol>
            
            <p class="trico-info-box">
                💡 <strong><?php _e('Tip:', 'trico-ai'); ?></strong>
                <?php _e('The more Groq API keys you add, the more requests you can handle. Keys are rotated automatically (machine-gun style) to maximize throughput.', 'trico-ai'); ?>
            </p>
        </div>
    </div>
</div>

<style>
.trico-alert {
    padding: 1.25rem;
}

.trico-alert-warning {
    background: #fef3c7;
    border-color: #f59e0b;
}

.trico-instructions {
    padding-left: 1.5rem;
}

.trico-instructions li {
    margin-bottom: 0.75rem;
}

.trico-instructions ul {
    margin-top: 0.5rem;
    padding-left: 1.5rem;
}

.trico-instructions code {
    background: var(--trico-light);
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.trico-info-box {
    background: #dbeafe;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-top: 1rem;
    margin-bottom: 0;
}

.text-warning {
    color: var(--trico-warning);
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.trico-reset-key').on('click', function() {
        var $btn = $(this);
        var keyIndex = $btn.data('key-index');
        
        $btn.prop('disabled', true).text('<?php _e('Resetting...', 'trico-ai'); ?>');
        
        $.ajax({
            url: tricoAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'trico_reset_api_key',
                nonce: tricoAdmin.nonce,
                key_index: keyIndex
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Failed to reset');
                    $btn.prop('disabled', false).text('<?php _e('Reset', 'trico-ai'); ?>');
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('<?php _e('Reset', 'trico-ai'); ?>');
            }
        });
    });
});
</script>
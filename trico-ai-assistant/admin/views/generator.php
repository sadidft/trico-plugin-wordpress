<?php
/**
 * Generator Page View
 * 
 * @package Trico_AI_Assistant
 */

defined('ABSPATH') || exit;
?>

<div class="wrap trico-wrap trico-generator-wrap">
    <div class="trico-header">
        <h1>
            <span class="trico-logo">✨</span>
            <?php _e('Generate Website', 'trico-ai'); ?>
        </h1>
        <p class="trico-tagline"><?php _e('Describe your website and let AI create it for you', 'trico-ai'); ?></p>
    </div>
    
    <div class="trico-generator-layout">
        <!-- Left: Prompt Section -->
        <div class="trico-prompt-section">
            <form id="trico-generate-form" class="trico-card">
                <div class="trico-form-group">
                    <label class="trico-form-label" for="project_name">
                        <?php _e('Project Name', 'trico-ai'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="project_name" 
                        name="project_name" 
                        class="trico-input"
                        placeholder="<?php esc_attr_e('e.g., Roti Masseh Website', 'trico-ai'); ?>"
                    >
                </div>
                
                <div class="trico-form-group">
                    <label class="trico-form-label" for="prompt">
                        <?php _e('Describe Your Website', 'trico-ai'); ?>
                        <span class="required">*</span>
                    </label>
                    <textarea 
                        id="prompt" 
                        name="prompt" 
                        class="trico-textarea"
                        rows="6"
                        placeholder="<?php esc_attr_e('Contoh: Buatkan landing page untuk toko roti modern bernama "Roti Masseh". Tampilkan hero section yang menarik dengan gambar roti, section fitur (roti fresh, delivery cepat, harga terjangkau), testimonial pelanggan, dan CTA untuk order via WhatsApp. Gunakan warna warm (coklat, cream, oranye). Style modern dan elegan.', 'trico-ai'); ?>"
                        required
                    ></textarea>
                    <p class="trico-form-hint">
                        <?php _e('Semakin detail deskripsi, semakin bagus hasilnya. Sebutkan: nama bisnis, warna, style, section yang diinginkan.', 'trico-ai'); ?>
                    </p>
                </div>
                
                <div class="trico-form-row">
                    <div class="trico-form-group trico-form-half">
                        <label class="trico-form-label" for="css_framework">
                            <?php _e('CSS Framework', 'trico-ai'); ?>
                        </label>
                        <select id="css_framework" name="css_framework" class="trico-select">
                            <?php foreach ($frameworks as $key => $framework): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($key, 'tailwind'); ?>>
                                <?php echo esc_html($framework['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="trico-form-group trico-form-half">
                        <label class="trico-form-label" for="language">
                            <?php _e('Content Language', 'trico-ai'); ?>
                        </label>
                        <select id="language" name="language" class="trico-select">
                            <option value="id"><?php _e('Indonesian', 'trico-ai'); ?></option>
                            <option value="en"><?php _e('English', 'trico-ai'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="trico-form-group">
                    <label class="trico-checkbox-label">
                        <input type="checkbox" name="upload_images" value="1">
                        <span><?php _e('Upload images to storage (slower, but images are permanent)', 'trico-ai'); ?></span>
                    </label>
                </div>
                
                <div class="trico-form-actions">
                    <button type="button" id="trico-preview-btn" class="trico-btn trico-btn-secondary">
                        👁️ <?php _e('Preview Only', 'trico-ai'); ?>
                    </button>
                    <button type="submit" id="trico-generate-btn" class="trico-btn trico-btn-primary">
                        ✨ <?php _e('Generate & Save', 'trico-ai'); ?>
                    </button>
                </div>
            </form>
            
            <!-- Tips -->
            <div class="trico-card trico-tips">
                <h3>💡 <?php _e('Tips for Better Results', 'trico-ai'); ?></h3>
                <ul>
                    <li><?php _e('Sebutkan nama bisnis/brand dengan jelas', 'trico-ai'); ?></li>
                    <li><?php _e('Jelaskan warna dan style yang diinginkan (modern, minimalist, colorful)', 'trico-ai'); ?></li>
                    <li><?php _e('Sebutkan section yang diinginkan (hero, features, about, testimonial, CTA)', 'trico-ai'); ?></li>
                    <li><?php _e('Berikan konteks bisnis untuk konten yang lebih relevan', 'trico-ai'); ?></li>
                </ul>
            </div>
            
            <!-- API Status -->
            <div class="trico-card trico-api-mini-status">
                <div class="trico-api-indicator">
                    <span class="trico-api-dot <?php echo trico()->api_manager->get_available_key_count() > 0 ? 'active' : 'inactive'; ?>"></span>
                    <span>
                        <?php 
                        $available = trico()->api_manager->get_available_key_count();
                        $total = trico()->api_manager->get_key_count();
                        printf(__('%d/%d API keys available', 'trico-ai'), $available, $total);
                        ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Right: Preview Section -->
        <div class="trico-preview-section">
            <div class="trico-card trico-preview-card">
                <div class="trico-preview-header">
                    <h3><?php _e('Live Preview', 'trico-ai'); ?></h3>
                    <div class="trico-preview-controls">
                        <button type="button" class="trico-preview-device active" data-device="desktop" title="Desktop">
                            💻
                        </button>
                        <button type="button" class="trico-preview-device" data-device="tablet" title="Tablet">
                            📱
                        </button>
                        <button type="button" class="trico-preview-device" data-device="mobile" title="Mobile">
                            📲
                        </button>
                    </div>
                </div>
                
                <div class="trico-preview-container" data-device="desktop">
                    <div class="trico-preview-placeholder" id="trico-preview-placeholder">
                        <div class="trico-preview-placeholder-content">
                            <span class="trico-preview-icon">🎨</span>
                            <p><?php _e('Your preview will appear here', 'trico-ai'); ?></p>
                            <small><?php _e('Enter a prompt and click Preview or Generate', 'trico-ai'); ?></small>
                        </div>
                    </div>
                    <iframe 
                        id="trico-preview-frame" 
                        class="trico-preview-frame" 
                        style="display: none;"
                        sandbox="allow-scripts allow-same-origin"
                    ></iframe>
                </div>
            </div>
            
            <!-- Generation Result -->
            <div id="trico-result" class="trico-card trico-result" style="display: none;">
                <h3>✅ <?php _e('Generation Complete!', 'trico-ai'); ?></h3>
                <div class="trico-result-stats">
                    <div class="trico-result-stat">
                        <span class="label"><?php _e('Time:', 'trico-ai'); ?></span>
                        <span class="value" id="result-time">-</span>
                    </div>
                    <div class="trico-result-stat">
                        <span class="label"><?php _e('Tokens:', 'trico-ai'); ?></span>
                        <span class="value" id="result-tokens">-</span>
                    </div>
                </div>
                <div class="trico-result-actions">
                    <a href="#" id="result-edit-link" class="trico-btn trico-btn-primary" target="_blank">
                        ✏️ <?php _e('Edit in WordPress', 'trico-ai'); ?>
                    </a>
                    <a href="#" id="result-preview-link" class="trico-btn trico-btn-secondary" target="_blank">
                        👁️ <?php _e('View Preview', 'trico-ai'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.trico-generator-wrap .trico-form-row {
    display: flex;
    gap: 1rem;
}

.trico-form-half {
    flex: 1;
}

.trico-form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.trico-checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.trico-tips {
    margin-top: 1rem;
    background: #fef3c7;
    border-color: #f59e0b;
}

.trico-tips h3 {
    margin: 0 0 0.5rem;
    font-size: 1rem;
}

.trico-tips ul {
    margin: 0;
    padding-left: 1.25rem;
}

.trico-tips li {
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}

.trico-api-mini-status {
    margin-top: 1rem;
    padding: 0.75rem 1rem;
}

.trico-api-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.trico-api-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #64748b;
}

.trico-api-dot.active {
    background: #10b981;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
}

.trico-api-dot.inactive {
    background: #ef4444;
}

/* Preview Section */
.trico-preview-card {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.trico-preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--trico-border);
    margin-bottom: 1rem;
}

.trico-preview-header h3 {
    margin: 0;
    font-size: 1rem;
}

.trico-preview-controls {
    display: flex;
    gap: 0.5rem;
}

.trico-preview-device {
    padding: 0.5rem;
    border: 1px solid var(--trico-border);
    border-radius: 0.375rem;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
}

.trico-preview-device:hover,
.trico-preview-device.active {
    border-color: var(--trico-primary);
    background: rgba(99, 102, 241, 0.05);
}

.trico-preview-container {
    flex: 1;
    min-height: 500px;
    border: 1px solid var(--trico-border);
    border-radius: 0.5rem;
    overflow: hidden;
    background: #f1f5f9;
    display: flex;
    justify-content: center;
}

.trico-preview-container[data-device="desktop"] {
    padding: 0;
}

.trico-preview-container[data-device="desktop"] .trico-preview-frame {
    width: 100%;
}

.trico-preview-container[data-device="tablet"] {
    padding: 1rem;
}

.trico-preview-container[data-device="tablet"] .trico-preview-frame {
    width: 768px;
    max-width: 100%;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border-radius: 0.5rem;
}

.trico-preview-container[data-device="mobile"] {
    padding: 1rem;
}

.trico-preview-container[data-device="mobile"] .trico-preview-frame {
    width: 375px;
    max-width: 100%;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border-radius: 0.5rem;
}

.trico-preview-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    min-height: 400px;
}

.trico-preview-placeholder-content {
    text-align: center;
    color: var(--trico-gray);
}

.trico-preview-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 1rem;
}

.trico-preview-frame {
    width: 100%;
    height: 100%;
    min-height: 500px;
    border: none;
    background: white;
}

/* Result Card */
.trico-result {
    margin-top: 1rem;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(99, 102, 241, 0.1));
    border-color: var(--trico-success);
}

.trico-result h3 {
    margin: 0 0 1rem;
    color: var(--trico-success);
}

.trico-result-stats {
    display: flex;
    gap: 2rem;
    margin-bottom: 1rem;
}

.trico-result-stat {
    display: flex;
    gap: 0.5rem;
}

.trico-result-stat .label {
    color: var(--trico-gray);
}

.trico-result-stat .value {
    font-weight: 600;
}

.trico-result-actions {
    display: flex;
    gap: 1rem;
}

@media (max-width: 1200px) {
    .trico-generator-layout {
        grid-template-columns: 1fr;
    }
    
    .trico-preview-section {
        order: -1;
    }
    
    .trico-preview-container {
        min-height: 400px;
    }
}

@media (max-width: 768px) {
    .trico-form-row {
        flex-direction: column;
    }
    
    .trico-form-actions {
        flex-direction: column;
    }
    
    .trico-result-stats {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .trico-result-actions {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    var $form = $('#trico-generate-form');
    var $previewBtn = $('#trico-preview-btn');
    var $generateBtn = $('#trico-generate-btn');
    var $previewFrame = $('#trico-preview-frame');
    var $placeholder = $('#trico-preview-placeholder');
    var $result = $('#trico-result');
    
    // Device switcher
    $('.trico-preview-device').on('click', function() {
        var device = $(this).data('device');
        $('.trico-preview-device').removeClass('active');
        $(this).addClass('active');
        $('.trico-preview-container').attr('data-device', device);
    });
    
    // Preview button
    $previewBtn.on('click', function() {
        var prompt = $('#prompt').val();
        
        if (!prompt.trim()) {
            alert('<?php _e('Please enter a prompt', 'trico-ai'); ?>');
            return;
        }
        
        doGenerate('trico_preview', $previewBtn);
    });
    
    // Generate button
    $form.on('submit', function(e) {
        e.preventDefault();
        doGenerate('trico_generate', $generateBtn);
    });
    
    function doGenerate(action, $btn) {
        var originalText = $btn.text();
        
        $btn.addClass('trico-btn-loading').prop('disabled', true).text('');
        $previewBtn.prop('disabled', true);
        $generateBtn.prop('disabled', true);
        
        $.ajax({
            url: tricoAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: action,
                nonce: tricoAdmin.nonce,
                prompt: $('#prompt').val(),
                project_name: $('#project_name').val(),
                css_framework: $('#css_framework').val(),
                language: $('#language').val(),
                upload_images: $('input[name="upload_images"]').is(':checked') ? 1 : 0
            },
            success: function(response) {
                $btn.removeClass('trico-btn-loading').prop('disabled', false).text(originalText);
                $previewBtn.prop('disabled', false);
                $generateBtn.prop('disabled', false);
                
                if (response.success) {
                    // Show preview
                    if (response.data.preview_html) {
                        showPreview(response.data.preview_html);
                    }
                    
                    // Show result if generated
                    if (response.data.edit_url) {
                        showResult(response.data);
                    }
                } else {
                    alert(response.data.message || '<?php _e('An error occurred', 'trico-ai'); ?>');
                }
            },
            error: function() {
                $btn.removeClass('trico-btn-loading').prop('disabled', false).text(originalText);
                $previewBtn.prop('disabled', false);
                $generateBtn.prop('disabled', false);
                alert('<?php _e('Request failed. Please try again.', 'trico-ai'); ?>');
            }
        });
    }
    
    function showPreview(html) {
        $placeholder.hide();
        $previewFrame.show();
        
        var doc = $previewFrame[0].contentDocument;
        doc.open();
        doc.write(html);
        doc.close();
    }
    
    function showResult(data) {
        $('#result-time').text(data.generation_time + 's');
        $('#result-tokens').text(data.tokens_used);
        $('#result-edit-link').attr('href', data.edit_url);
        $('#result-preview-link').attr('href', data.preview_url);
        $result.slideDown();
    }
});
</script>
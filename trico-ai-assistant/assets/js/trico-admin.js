/**
 * Trico Admin JavaScript
 */

(function($) {
    'use strict';
    
    window.TricoAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initTooltips();
        },
        
        bindEvents: function() {
            // Generate button
            $(document).on('click', '.trico-generate-btn', this.handleGenerate.bind(this));
            
            // Deploy button
            $(document).on('click', '.trico-deploy-btn', this.handleDeploy.bind(this));
            
            // Delete project
            $(document).on('click', '.trico-delete-project', this.handleDelete.bind(this));
            
            // Copy to clipboard
            $(document).on('click', '.trico-copy-btn', this.handleCopy.bind(this));
        },
        
        initTooltips: function() {
            // Simple tooltip initialization if needed
        },
        
        handleGenerate: function(e) {
            e.preventDefault();
            
            var $btn = $(e.currentTarget);
            var $form = $btn.closest('form');
            var prompt = $form.find('[name="prompt"]').val();
            
            if (!prompt.trim()) {
                this.showNotice('Please enter a prompt', 'error');
                return;
            }
            
            this.setButtonLoading($btn, true);
            
            $.ajax({
                url: tricoAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'trico_generate',
                    nonce: tricoAdmin.nonce,
                    prompt: prompt,
                    project_name: $form.find('[name="project_name"]').val(),
                    css_framework: $form.find('[name="css_framework"]').val()
                },
                success: function(response) {
                    this.setButtonLoading($btn, false);
                    
                    if (response.success) {
                        this.showNotice(tricoAdmin.strings.success, 'success');
                        
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                        
                        if (response.data.preview_html) {
                            this.updatePreview(response.data.preview_html);
                        }
                    } else {
                        this.showNotice(response.data.message || tricoAdmin.strings.error, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.setButtonLoading($btn, false);
                    this.showNotice(tricoAdmin.strings.error, 'error');
                }.bind(this)
            });
        },
        
        handleDeploy: function(e) {
            e.preventDefault();
            
            var $btn = $(e.currentTarget);
            var projectId = $btn.data('project-id');
            
            this.setButtonLoading($btn, true);
            
            $.ajax({
                url: tricoAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'trico_deploy',
                    nonce: tricoAdmin.nonce,
                    project_id: projectId
                },
                success: function(response) {
                    this.setButtonLoading($btn, false);
                    
                    if (response.success) {
                        this.showNotice('Deployed successfully!', 'success');
                        
                        if (response.data.url) {
                            window.open(response.data.url, '_blank');
                        }
                    } else {
                        this.showNotice(response.data.message || 'Deployment failed', 'error');
                    }
                }.bind(this),
                error: function() {
                    this.setButtonLoading($btn, false);
                    this.showNotice('Deployment failed', 'error');
                }.bind(this)
            });
        },
        
        handleDelete: function(e) {
            e.preventDefault();
            
            if (!confirm(tricoAdmin.strings.confirm_delete)) {
                return;
            }
            
            var $btn = $(e.currentTarget);
            var projectId = $btn.data('project-id');
            var $row = $btn.closest('tr');
            
            $.ajax({
                url: tricoAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'trico_delete_project',
                    nonce: tricoAdmin.nonce,
                    project_id: projectId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        this.showNotice('Project deleted', 'success');
                    } else {
                        this.showNotice(response.data.message || 'Delete failed', 'error');
                    }
                }.bind(this)
            });
        },
        
        handleCopy: function(e) {
            e.preventDefault();
            
            var $btn = $(e.currentTarget);
            var text = $btn.data('copy-text');
            
            navigator.clipboard.writeText(text).then(function() {
                var originalText = $btn.text();
                $btn.text('Copied!');
                setTimeout(function() {
                    $btn.text(originalText);
                }, 2000);
            });
        },
        
        setButtonLoading: function($btn, loading) {
            if (loading) {
                $btn.addClass('trico-btn-loading').prop('disabled', true);
                $btn.data('original-text', $btn.text());
                $btn.text('');
            } else {
                $btn.removeClass('trico-btn-loading').prop('disabled', false);
                $btn.text($btn.data('original-text'));
            }
        },
        
        updatePreview: function(html) {
            var $frame = $('.trico-preview-frame');
            if ($frame.length) {
                var doc = $frame[0].contentDocument;
                doc.open();
                doc.write(html);
                doc.close();
            }
        },
        
        showNotice: function(message, type) {
            type = type || 'info';
            
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.trico-wrap h1').first().after($notice);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
    $(document).ready(function() {
        TricoAdmin.init();
    });
    
})(jQuery);
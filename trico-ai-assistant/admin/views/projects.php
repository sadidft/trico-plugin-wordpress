<?php
/**
 * Projects List View
 * 
 * @package Trico_AI_Assistant
 */

defined('ABSPATH') || exit;
?>

<div class="wrap trico-wrap">
    <div class="trico-header">
        <div class="trico-header-left">
            <h1>
                <span class="trico-logo">📁</span>
                <?php _e('Projects', 'trico-ai'); ?>
            </h1>
            <p class="trico-tagline"><?php _e('Manage your generated websites', 'trico-ai'); ?></p>
        </div>
        <div class="trico-header-right">
            <a href="<?php echo admin_url('admin.php?page=trico-generator'); ?>" class="trico-btn trico-btn-primary">
                ✨ <?php _e('Generate New', 'trico-ai'); ?>
            </a>
        </div>
    </div>
    
    <?php if (empty($projects)): ?>
    
    <div class="trico-card trico-empty-state">
        <div class="trico-empty-icon">🎨</div>
        <h2><?php _e('No projects yet', 'trico-ai'); ?></h2>
        <p><?php _e('Generate your first website using AI', 'trico-ai'); ?></p>
        <a href="<?php echo admin_url('admin.php?page=trico-generator'); ?>" class="trico-btn trico-btn-primary">
            <?php _e('Create First Project', 'trico-ai'); ?>
        </a>
    </div>
    
    <?php else: ?>
    
    <!-- Filter/Search -->
    <div class="trico-card trico-toolbar">
        <div class="trico-toolbar-left">
            <select id="filter-status" class="trico-select-small">
                <option value=""><?php _e('All Status', 'trico-ai'); ?></option>
                <option value="draft"><?php _e('Draft', 'trico-ai'); ?></option>
                <option value="published"><?php _e('Published', 'trico-ai'); ?></option>
            </select>
            
            <select id="filter-framework" class="trico-select-small">
                <option value=""><?php _e('All Frameworks', 'trico-ai'); ?></option>
                <option value="tailwind">Tailwind</option>
                <option value="bootstrap">Bootstrap</option>
                <option value="vanilla">Vanilla</option>
            </select>
        </div>
        
        <div class="trico-toolbar-right">
            <input type="text" id="search-projects" class="trico-input-small" placeholder="<?php esc_attr_e('Search projects...', 'trico-ai'); ?>">
        </div>
    </div>
    
    <!-- Projects Grid -->
    <div class="trico-projects-grid">
        <?php foreach ($projects as $project): ?>
        <div class="trico-project-card" data-status="<?php echo esc_attr($project['status']); ?>" data-framework="<?php echo esc_attr($project['css_framework']); ?>">
            <div class="trico-project-header">
                <div class="trico-project-status">
                    <?php if ($project['status'] === 'published'): ?>
                        <span class="trico-badge trico-badge-success"><?php _e('Live', 'trico-ai'); ?></span>
                    <?php else: ?>
                        <span class="trico-badge trico-badge-secondary"><?php _e('Draft', 'trico-ai'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="trico-project-framework">
                    <span class="trico-badge trico-badge-outline"><?php echo esc_html(ucfirst($project['css_framework'] ?? 'tailwind')); ?></span>
                </div>
            </div>
            
            <div class="trico-project-body">
                <h3 class="trico-project-name"><?php echo esc_html($project['name']); ?></h3>
                
                <?php if (!empty($project['subdomain'])): ?>
                <p class="trico-project-url">
                    <code><?php echo esc_html($project['subdomain'] . '.' . TRICO_DEFAULT_DOMAIN); ?></code>
                </p>
                <?php endif; ?>
                
                <div class="trico-project-meta">
                    <span title="<?php esc_attr_e('Last updated', 'trico-ai'); ?>">
                        🕐 <?php echo esc_html(human_time_diff(strtotime($project['updated_at']))); ?> ago
                    </span>
                    <?php if (!empty($project['author_name'])): ?>
                    <span title="<?php esc_attr_e('Author', 'trico-ai'); ?>">
                        👤 <?php echo esc_html($project['author_name']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="trico-project-actions">
                <?php if (!empty($project['post_id'])): ?>
                <a href="<?php echo get_edit_post_link($project['post_id']); ?>" class="trico-btn-icon" title="<?php esc_attr_e('Edit in WordPress', 'trico-ai'); ?>">
                    ✏️
                </a>
                <a href="<?php echo get_preview_post_link($project['post_id']); ?>" class="trico-btn-icon" target="_blank" title="<?php esc_attr_e('Preview', 'trico-ai'); ?>">
                    👁️
                </a>
                <?php endif; ?>
                
                <?php if ($project['status'] === 'published' && !empty($project['cf_deployment_url'])): ?>
                <a href="<?php echo esc_url($project['cf_deployment_url']); ?>" class="trico-btn-icon" target="_blank" title="<?php esc_attr_e('View Live', 'trico-ai'); ?>">
                    🌐
                </a>
                <?php else: ?>
                <button type="button" class="trico-btn-icon trico-deploy-btn" data-project-id="<?php echo esc_attr($project['id']); ?>" title="<?php esc_attr_e('Deploy', 'trico-ai'); ?>">
                    🚀
                </button>
                <?php endif; ?>
                
                <button type="button" class="trico-btn-icon trico-project-menu-btn" data-project-id="<?php echo esc_attr($project['id']); ?>">
                    ⋮
                </button>
                
                <div class="trico-project-menu" id="menu-<?php echo esc_attr($project['id']); ?>">
                    <a href="#" class="trico-project-menu-item trico-view-history" data-project-id="<?php echo esc_attr($project['id']); ?>">
                        📜 <?php _e('View History', 'trico-ai'); ?>
                    </a>
                    <a href="#" class="trico-project-menu-item trico-export-project" data-project-id="<?php echo esc_attr($project['id']); ?>">
                        📦 <?php _e('Export Static', 'trico-ai'); ?>
                    </a>
                    <a href="#" class="trico-project-menu-item trico-duplicate-project" data-project-id="<?php echo esc_attr($project['id']); ?>">
                        📋 <?php _e('Duplicate', 'trico-ai'); ?>
                    </a>
                    <hr>
                    <a href="#" class="trico-project-menu-item trico-delete-project danger" data-project-id="<?php echo esc_attr($project['id']); ?>">
                        🗑️ <?php _e('Delete', 'trico-ai'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php endif; ?>
</div>

<!-- History Modal -->
<div id="trico-history-modal" class="trico-modal" style="display: none;">
    <div class="trico-modal-content">
        <div class="trico-modal-header">
            <h2><?php _e('Generation History', 'trico-ai'); ?></h2>
            <button type="button" class="trico-modal-close">&times;</button>
        </div>
        <div class="trico-modal-body" id="trico-history-content">
            <!-- Loaded via AJAX -->
        </div>
    </div>
</div>

<style>
.trico-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.trico-empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.trico-empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.trico-empty-state h2 {
    margin: 0 0 0.5rem;
    color: var(--trico-dark);
}

.trico-empty-state p {
    color: var(--trico-gray);
    margin-bottom: 1.5rem;
}

/* Toolbar */
.trico-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.trico-toolbar-left,
.trico-toolbar-right {
    display: flex;
    gap: 0.75rem;
}

.trico-select-small,
.trico-input-small {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    border: 1px solid var(--trico-border);
    border-radius: 0.375rem;
}

/* Projects Grid */
.trico-projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.trico-project-card {
    background: #fff;
    border: 1px solid var(--trico-border);
    border-radius: var(--trico-radius);
    overflow: hidden;
    transition: all 0.2s ease;
}

.trico-project-card:hover {
    border-color: var(--trico-primary);
    box-shadow: var(--trico-shadow);
}

.trico-project-header {
    display: flex;
    justify-content: space-between;
    padding: 1rem;
    background: var(--trico-light);
    border-bottom: 1px solid var(--trico-border);
}

.trico-project-body {
    padding: 1.25rem;
}

.trico-project-name {
    margin: 0 0 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
}

.trico-project-url {
    margin: 0 0 1rem;
}

.trico-project-url code {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    background: var(--trico-light);
    border-radius: 0.25rem;
}

.trico-project-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--trico-gray);
}

.trico-project-actions {
    display: flex;
    gap: 0.5rem;
    padding: 1rem;
    border-top: 1px solid var(--trico-border);
    position: relative;
}

.trico-btn-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--trico-light);
    border: 1px solid var(--trico-border);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: 1rem;
}

.trico-btn-icon:hover {
    background: #fff;
    border-color: var(--trico-primary);
}

/* Project Menu */
.trico-project-menu {
    position: absolute;
    right: 1rem;
    bottom: 100%;
    background: #fff;
    border: 1px solid var(--trico-border);
    border-radius: 0.5rem;
    box-shadow: var(--trico-shadow-lg);
    min-width: 180px;
    display: none;
    z-index: 100;
}

.trico-project-menu.active {
    display: block;
}

.trico-project-menu-item {
    display: block;
    padding: 0.75rem 1rem;
    color: var(--trico-dark);
    text-decoration: none;
    font-size: 0.875rem;
    transition: background 0.2s ease;
}

.trico-project-menu-item:hover {
    background: var(--trico-light);
}

.trico-project-menu-item.danger {
    color: var(--trico-error);
}

.trico-project-menu hr {
    margin: 0;
    border: none;
    border-top: 1px solid var(--trico-border);
}

/* Badge outline */
.trico-badge-outline {
    background: transparent;
    border: 1px solid var(--trico-border);
    color: var(--trico-gray);
}

/* Modal */
.trico-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.trico-modal-content {
    background: #fff;
    border-radius: var(--trico-radius);
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.trico-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--trico-border);
}

.trico-modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
}

.trico-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--trico-gray);
}

.trico-modal-body {
    padding: 1.5rem;
    overflow-y: auto;
}

@media (max-width: 768px) {
    .trico-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .trico-toolbar {
        flex-direction: column;
        gap: 1rem;
    }
    
    .trico-projects-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle project menu
    $(document).on('click', '.trico-project-menu-btn', function(e) {
        e.stopPropagation();
        var projectId = $(this).data('project-id');
        var $menu = $('#menu-' + projectId);
        
        // Close other menus
        $('.trico-project-menu').not($menu).removeClass('active');
        
        $menu.toggleClass('active');
    });
    
    // Close menu on outside click
    $(document).on('click', function() {
        $('.trico-project-menu').removeClass('active');
    });
    
    // Search filter
    $('#search-projects').on('input', function() {
        var search = $(this).val().toLowerCase();
        
        $('.trico-project-card').each(function() {
            var name = $(this).find('.trico-project-name').text().toLowerCase();
            $(this).toggle(name.indexOf(search) > -1);
        });
    });
    
    // Status filter
    $('#filter-status').on('change', function() {
        var status = $(this).val();
        
        $('.trico-project-card').each(function() {
            if (!status) {
                $(this).show();
            } else {
                $(this).toggle($(this).data('status') === status);
            }
        });
    });
    
    // Framework filter
    $('#filter-framework').on('change', function() {
        var framework = $(this).val();
        
        $('.trico-project-card').each(function() {
            if (!framework) {
                $(this).show();
            } else {
                $(this).toggle($(this).data('framework') === framework);
            }
        });
    });
    
    // View history
    $(document).on('click', '.trico-view-history', function(e) {
        e.preventDefault();
        var projectId = $(this).data('project-id');
        
        $('#trico-history-content').html('<p>Loading...</p>');
        $('#trico-history-modal').show();
        
        $.ajax({
            url: tricoAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'trico_get_project',
                nonce: tricoAdmin.nonce,
                project_id: projectId
            },
            success: function(response) {
                if (response.success && response.data.history) {
                    var html = '<div class="trico-history-list">';
                    
                    if (response.data.history.length === 0) {
                        html += '<p>No history available.</p>';
                    } else {
                        response.data.history.forEach(function(item) {
                            html += '<div class="trico-history-item">';
                            html += '<div class="trico-history-prompt">' + item.prompt.substring(0, 100) + '...</div>';
                            html += '<div class="trico-history-meta">';
                            html += '<span>Model: ' + item.ai_model + '</span>';
                            html += '<span>Time: ' + item.generation_time + 's</span>';
                            html += '<span>Tokens: ' + item.tokens_used + '</span>';
                            html += '</div>';
                            html += '</div>';
                        });
                    }
                    
                    html += '</div>';
                    $('#trico-history-content').html(html);
                }
            }
        });
    });
    
    // Close modal
    $(document).on('click', '.trico-modal-close, .trico-modal', function(e) {
        if (e.target === this) {
            $('#trico-history-modal').hide();
        }
    });
    
    // Delete project
    $(document).on('click', '.trico-delete-project', function(e) {
        e.preventDefault();
        
        if (!confirm(tricoAdmin.strings.confirm_delete)) {
            return;
        }
        
        var projectId = $(this).data('project-id');
        var $card = $(this).closest('.trico-project-card');
        
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
                    $card.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message || 'Failed to delete');
                }
            }
        });
    });
});
</script>
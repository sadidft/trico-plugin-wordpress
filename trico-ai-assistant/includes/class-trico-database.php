<?php
/**
 * Trico Database Handler
 * TiDB Compatible (No Foreign Keys)
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_Database {
    
    private $charset_collate;
    private $projects_table;
    private $history_table;
    private $b2_table;
    
    public function __construct() {
        global $wpdb;
        
        $this->charset_collate = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci';
        $this->projects_table = $wpdb->base_prefix . 'trico_projects';
        $this->history_table = $wpdb->base_prefix . 'trico_history';
        $this->b2_table = $wpdb->base_prefix . 'trico_b2_files';
    }
    
    public function create_tables() {
        global $wpdb;
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Projects Table
        $sql_projects = "CREATE TABLE IF NOT EXISTS {$this->projects_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            site_id bigint(20) unsigned NOT NULL DEFAULT 1,
            user_id bigint(20) unsigned NOT NULL,
            name varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            subdomain varchar(100) DEFAULT NULL,
            custom_domain varchar(255) DEFAULT NULL,
            post_id bigint(20) unsigned DEFAULT NULL,
            css_content longtext DEFAULT NULL,
            js_content longtext DEFAULT NULL,
            seo_title varchar(255) DEFAULT NULL,
            seo_description text DEFAULT NULL,
            seo_keywords text DEFAULT NULL,
            og_image_url varchar(500) DEFAULT NULL,
            css_framework varchar(20) DEFAULT 'tailwind',
            pwa_enabled tinyint(1) DEFAULT 0,
            analytics_enabled tinyint(1) DEFAULT 1,
            cf_project_name varchar(100) DEFAULT NULL,
            cf_deployment_url varchar(255) DEFAULT NULL,
            cf_last_deployment_id varchar(100) DEFAULT NULL,
            last_deployed_at datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_site_user (site_id, user_id),
            KEY idx_slug (slug),
            KEY idx_status (status)
        ) {$this->charset_collate};";
        
        dbDelta($sql_projects);
        
        // History Table
        $sql_history = "CREATE TABLE IF NOT EXISTS {$this->history_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            prompt text NOT NULL,
            blocks_content longtext DEFAULT NULL,
            css_content longtext DEFAULT NULL,
            js_content longtext DEFAULT NULL,
            image_prompts text DEFAULT NULL,
            ai_model varchar(50) DEFAULT NULL,
            generation_time float DEFAULT NULL,
            tokens_used int(10) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_project (project_id),
            KEY idx_created (created_at)
        ) {$this->charset_collate};";
        
        dbDelta($sql_history);
        
        // B2 Files Table
        $sql_b2 = "CREATE TABLE IF NOT EXISTS {$this->b2_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            site_id bigint(20) unsigned NOT NULL,
            wp_attachment_id bigint(20) unsigned DEFAULT NULL,
            b2_file_id varchar(255) NOT NULL,
            b2_file_name varchar(500) NOT NULL,
            b2_url varchar(500) NOT NULL,
            file_size bigint(20) unsigned DEFAULT NULL,
            mime_type varchar(100) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_site (site_id),
            KEY idx_attachment (wp_attachment_id)
        ) {$this->charset_collate};";
        
        dbDelta($sql_b2);
    }
    
    public function drop_tables() {
        global $wpdb;
        
        $wpdb->query("DROP TABLE IF EXISTS {$this->history_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->b2_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->projects_table}");
    }
    
    // ==========================================
    // PROJECT OPERATIONS
    // ==========================================
    
    public function create_project($data) {
        global $wpdb;
        
        $defaults = array(
            'site_id' => get_current_blog_id(),
            'user_id' => get_current_user_id(),
            'status' => 'draft',
            'css_framework' => 'tailwind',
            'pwa_enabled' => 0,
            'analytics_enabled' => 1
        );
        
        $data = wp_parse_args($data, $defaults);
        
        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['name']);
        }
        
        $result = $wpdb->insert($this->projects_table, $data);
        
        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }
        
        return $wpdb->insert_id;
    }
    
    public function get_project($id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->projects_table} WHERE id = %d", $id),
            ARRAY_A
        );
    }
    
    public function get_project_by_slug($slug, $site_id = null) {
        global $wpdb;
        
        if (is_null($site_id)) {
            $site_id = get_current_blog_id();
        }
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->projects_table} WHERE slug = %s AND site_id = %d",
                $slug,
                $site_id
            ),
            ARRAY_A
        );
    }
    
    public function get_user_projects($user_id = null, $site_id = null, $limit = 50) {
        global $wpdb;
        
        if (is_null($user_id)) {
            $user_id = get_current_user_id();
        }
        
        if (is_null($site_id)) {
            $site_id = get_current_blog_id();
        }
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->projects_table} 
                WHERE user_id = %d AND site_id = %d 
                ORDER BY updated_at DESC 
                LIMIT %d",
                $user_id,
                $site_id,
                $limit
            ),
            ARRAY_A
        );
    }
    
    public function get_all_projects($site_id = null, $limit = 100) {
        global $wpdb;
        
        if (is_null($site_id)) {
            $site_id = get_current_blog_id();
        }
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.*, u.display_name as author_name 
                FROM {$this->projects_table} p
                LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
                WHERE p.site_id = %d 
                ORDER BY p.updated_at DESC 
                LIMIT %d",
                $site_id,
                $limit
            ),
            ARRAY_A
        );
    }
    
    public function update_project($id, $data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            $this->projects_table,
            $data,
            array('id' => $id)
        );
        
        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }
        
        return true;
    }
    
    public function delete_project($id) {
        global $wpdb;
        
        $wpdb->delete($this->history_table, array('project_id' => $id));
        return $wpdb->delete($this->projects_table, array('id' => $id));
    }
    
    public function get_project_count($site_id = null, $user_id = null) {
        global $wpdb;
        
        $where = array('1=1');
        $values = array();
        
        if (!is_null($site_id)) {
            $where[] = 'site_id = %d';
            $values[] = $site_id;
        }
        
        if (!is_null($user_id)) {
            $where[] = 'user_id = %d';
            $values[] = $user_id;
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->projects_table} WHERE " . implode(' AND ', $where);
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        return (int) $wpdb->get_var($sql);
    }
    
    // ==========================================
    // HISTORY OPERATIONS (FIFO - Max 4)
    // ==========================================
    
    public function add_history($project_id, $data) {
        global $wpdb;
        
        $this->cleanup_history($project_id, 3);
        
        $data['project_id'] = $project_id;
        
        $result = $wpdb->insert($this->history_table, $data);
        
        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }
        
        return $wpdb->insert_id;
    }
    
    public function get_project_history($project_id, $limit = 4) {
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->history_table} 
                WHERE project_id = %d 
                ORDER BY created_at DESC 
                LIMIT %d",
                $project_id,
                $limit
            ),
            ARRAY_A
        );
    }
    
    public function get_history($id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->history_table} WHERE id = %d", $id),
            ARRAY_A
        );
    }
    
    private function cleanup_history($project_id, $keep_count) {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->history_table} WHERE project_id = %d",
                $project_id
            )
        );
        
        if ($count >= $keep_count) {
            $delete_count = $count - $keep_count + 1;
            
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$this->history_table} 
                    WHERE project_id = %d 
                    ORDER BY created_at ASC 
                    LIMIT %d",
                    $project_id,
                    $delete_count
                )
            );
        }
    }
    
    // ==========================================
    // STATS
    // ==========================================
    
    public function get_stats($site_id = null) {
        global $wpdb;
        
        if (is_null($site_id)) {
            $site_id = get_current_blog_id();
        }
        
        $total_projects = $this->get_project_count($site_id);
        
        $deployed = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->projects_table} 
                WHERE site_id = %d AND status = 'published'",
                $site_id
            )
        );
        
        $total_generations = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->history_table} h
                INNER JOIN {$this->projects_table} p ON h.project_id = p.id
                WHERE p.site_id = %d",
                $site_id
            )
        );
        
        return array(
            'total_projects' => (int) $total_projects,
            'deployed' => (int) $deployed,
            'drafts' => (int) ($total_projects - $deployed),
            'total_generations' => (int) $total_generations
        );
    }
}

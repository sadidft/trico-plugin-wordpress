<?php
/**
 * Trico Analytics (Synalytics)
 * Cloudflare Web Analytics integration
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_Analytics {
    
    private $cloudflare;
    
    public function __construct() {
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-cloudflare.php';
        $this->cloudflare = new Trico_Cloudflare();
    }
    
    /**
     * Get analytics for a project
     */
    public function get_project_analytics($project_id, $days = 7) {
        $project = trico()->database->get_project($project_id);
        
        if (!$project) {
            return new WP_Error('not_found', 'Project not found');
        }
        
        // Check if analytics is enabled
        if (empty($project['analytics_enabled'])) {
            return array(
                'enabled' => false,
                'message' => __('Analytics not enabled for this project', 'trico-ai')
            );
        }
        
        // Get site tag (from project or generate)
        $site_tag = get_post_meta($project['post_id'] ?? 0, '_trico_analytics_site_tag', true);
        
        if (empty($site_tag)) {
            // Try to create analytics site
            $domain = $this->get_project_domain($project);
            $create_result = $this->cloudflare->create_analytics_site($domain);
            
            if (!is_wp_error($create_result) && isset($create_result['result']['site_tag'])) {
                $site_tag = $create_result['result']['site_tag'];
                update_post_meta($project['post_id'], '_trico_analytics_site_tag', $site_tag);
            } else {
                return array(
                    'enabled' => true,
                    'configured' => false,
                    'message' => __('Analytics site not configured', 'trico-ai')
                );
            }
        }
        
        // Fetch analytics data
        $data = $this->cloudflare->get_analytics_summary($site_tag, $days);
        
        if (is_wp_error($data)) {
            return $data;
        }
        
        return array(
            'enabled' => true,
            'configured' => true,
            'site_tag' => $site_tag,
            'period' => $days . ' days',
            'data' => $data
        );
    }
    
    /**
     * Get analytics for all user projects
     */
    public function get_all_analytics($user_id = null, $days = 7) {
        if (is_null($user_id)) {
            $user_id = get_current_user_id();
        }
        
        $projects = trico()->database->get_user_projects($user_id);
        $analytics = array();
        
        foreach ($projects as $project) {
            if ($project['status'] !== 'published') {
                continue;
            }
            
            $project_analytics = $this->get_project_analytics($project['id'], $days);
            
            if (!is_wp_error($project_analytics) && isset($project_analytics['data'])) {
                $analytics[] = array(
                    'project_id' => $project['id'],
                    'project_name' => $project['name'],
                    'url' => $project['cf_deployment_url'],
                    'analytics' => $project_analytics['data']
                );
            }
        }
        
        return $analytics;
    }
    
    /**
     * Get aggregated stats
     */
    public function get_aggregated_stats($days = 7) {
        $all_analytics = $this->get_all_analytics(null, $days);
        
        $total_visits = 0;
        $total_pageviews = 0;
        $all_countries = array();
        $all_devices = array();
        
        foreach ($all_analytics as $project) {
            $data = $project['analytics'];
            
            $total_visits += $data['total_visits'] ?? 0;
            $total_pageviews += $data['total_pageviews'] ?? 0;
            
            // Aggregate countries
            foreach ($data['top_countries'] ?? array() as $country) {
                $name = $country['country'];
                if (!isset($all_countries[$name])) {
                    $all_countries[$name] = 0;
                }
                $all_countries[$name] += $country['visits'];
            }
            
            // Aggregate devices
            foreach ($data['devices'] ?? array() as $device) {
                $type = $device['type'];
                if (!isset($all_devices[$type])) {
                    $all_devices[$type] = 0;
                }
                $all_devices[$type] += $device['visits'];
            }
        }
        
        // Sort countries and devices
        arsort($all_countries);
        arsort($all_devices);
        
        // Format countries
        $top_countries = array();
        foreach (array_slice($all_countries, 0, 10, true) as $name => $visits) {
            $top_countries[] = array('country' => $name, 'visits' => $visits);
        }
        
        // Format devices
        $devices = array();
        foreach ($all_devices as $type => $visits) {
            $devices[] = array('type' => $type, 'visits' => $visits);
        }
        
        return array(
            'total_visits' => $total_visits,
            'total_pageviews' => $total_pageviews,
            'projects_count' => count($all_analytics),
            'top_countries' => $top_countries,
            'devices' => $devices,
            'projects' => $all_analytics
        );
    }
    
    /**
     * Get project domain
     */
    private function get_project_domain($project) {
        if (!empty($project['custom_domain'])) {
            return $project['custom_domain'];
        }
        
        if (!empty($project['subdomain'])) {
            return $project['subdomain'] . '.' . trico()->core->get_site_domain();
        }
        
        if (!empty($project['cf_project_name'])) {
            return $project['cf_project_name'] . '.pages.dev';
        }
        
        return $project['slug'] . '.' . trico()->core->get_site_domain();
    }
    
    /**
     * Generate analytics embed script
     */
    public function get_analytics_script($site_tag) {
        if (empty($site_tag)) {
            return '';
        }
        
        return <<<HTML
<!-- Cloudflare Web Analytics -->
<script defer src='https://static.cloudflareinsights.com/beacon.min.js' data-cf-beacon='{"token": "{$site_tag}"}'></script>
<!-- End Cloudflare Web Analytics -->
HTML;
    }
    
    /**
     * Add analytics script to exported pages
     */
    public function inject_analytics_script($html, $project) {
        if (empty($project['analytics_enabled'])) {
            return $html;
        }
        
        $site_tag = get_post_meta($project['post_id'] ?? 0, '_trico_analytics_site_tag', true);
        
        if (empty($site_tag)) {
            return $html;
        }
        
        $script = $this->get_analytics_script($site_tag);
        
        // Inject before </body>
        $html = str_replace('</body>', $script . "\n</body>", $html);
        
        return $html;
    }
    
    /**
     * Get realtime-like recent data
     */
    public function get_recent_activity($project_id, $hours = 24) {
        $project = trico()->database->get_project($project_id);
        
        if (!$project) {
            return new WP_Error('not_found', 'Project not found');
        }
        
        $site_tag = get_post_meta($project['post_id'] ?? 0, '_trico_analytics_site_tag', true);
        
        if (empty($site_tag)) {
            return array();
        }
        
        // CF Analytics doesn't have true realtime, use last 24h
        $since = date('Y-m-d\TH:i:s\Z', strtotime("-{$hours} hours"));
        $until = date('Y-m-d\TH:i:s\Z');
        
        $data = $this->cloudflare->get_analytics($site_tag, $since, $until);
        
        if (is_wp_error($data)) {
            return $data;
        }
        
        return $data;
    }
}
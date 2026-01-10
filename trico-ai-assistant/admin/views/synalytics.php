<?php
/**
 * Synalytics Dashboard View
 * 
 * @package Trico_AI_Assistant
 */

defined('ABSPATH') || exit;

// Get analytics data
require_once TRICO_PLUGIN_DIR . 'includes/class-trico-analytics.php';
$analytics_handler = new Trico_Analytics();
$stats = $analytics_handler->get_aggregated_stats(7);

$period = isset($_GET['period']) ? intval($_GET['period']) : 7;
?>

<div class="wrap trico-wrap trico-synalytics-wrap">
    <div class="trico-header">
        <div class="trico-header-left">
            <h1>
                <span class="trico-logo">📊</span>
                <?php _e('Synalytics', 'trico-ai'); ?>
            </h1>
            <p class="trico-tagline"><?php _e('Analytics for your deployed websites', 'trico-ai'); ?></p>
        </div>
        <div class="trico-header-right">
            <select id="synalytics-period" class="trico-select">
                <option value="7" <?php selected($period, 7); ?>><?php _e('Last 7 days', 'trico-ai'); ?></option>
                <option value="30" <?php selected($period, 30); ?>><?php _e('Last 30 days', 'trico-ai'); ?></option>
                <option value="90" <?php selected($period, 90); ?>><?php _e('Last 90 days', 'trico-ai'); ?></option>
            </select>
        </div>
    </div>
    
    <?php if (empty($stats['projects'])): ?>
    
    <div class="trico-card trico-empty-state">
        <div class="trico-empty-icon">📊</div>
        <h2><?php _e('No analytics data yet', 'trico-ai'); ?></h2>
        <p><?php _e('Deploy your first website to start tracking analytics', 'trico-ai'); ?></p>
        <a href="<?php echo admin_url('admin.php?page=trico-generator'); ?>" class="trico-btn trico-btn-primary">
            <?php _e('Generate Website', 'trico-ai'); ?>
        </a>
    </div>
    
    <?php else: ?>
    
    <!-- Overview Stats -->
    <div class="trico-stats-grid">
        <div class="trico-stat-card trico-stat-primary">
            <div class="trico-stat-icon">👁️</div>
            <div class="trico-stat-content">
                <span class="trico-stat-value"><?php echo number_format($stats['total_visits']); ?></span>
                <span class="trico-stat-label"><?php _e('Total Visitors', 'trico-ai'); ?></span>
            </div>
        </div>
        
        <div class="trico-stat-card">
            <div class="trico-stat-icon">📄</div>
            <div class="trico-stat-content">
                <span class="trico-stat-value"><?php echo number_format($stats['total_pageviews']); ?></span>
                <span class="trico-stat-label"><?php _e('Page Views', 'trico-ai'); ?></span>
            </div>
        </div>
        
        <div class="trico-stat-card">
            <div class="trico-stat-icon">🌐</div>
            <div class="trico-stat-content">
                <span class="trico-stat-value"><?php echo number_format($stats['projects_count']); ?></span>
                <span class="trico-stat-label"><?php _e('Active Sites', 'trico-ai'); ?></span>
            </div>
        </div>
        
        <div class="trico-stat-card">
            <div class="trico-stat-icon">📈</div>
            <div class="trico-stat-content">
                <?php 
                $avg_per_site = $stats['projects_count'] > 0 
                    ? round($stats['total_visits'] / $stats['projects_count']) 
                    : 0;
                ?>
                <span class="trico-stat-value"><?php echo number_format($avg_per_site); ?></span>
                <span class="trico-stat-label"><?php _e('Avg per Site', 'trico-ai'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="trico-charts-row">
        <!-- Traffic Chart -->
        <div class="trico-card trico-chart-card trico-chart-wide">
            <h3><?php _e('Visitors Over Time', 'trico-ai'); ?></h3>
            <div class="trico-chart-container">
                <canvas id="trico-visitors-chart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Data Row -->
    <div class="trico-data-row">
        <!-- Top Pages -->
        <div class="trico-card">
            <h3><?php _e('Top Pages', 'trico-ai'); ?></h3>
            <div class="trico-data-list">
                <?php 
                $all_pages = array();
                foreach ($stats['projects'] as $project) {
                    foreach ($project['analytics']['top_pages'] ?? array() as $page) {
                        $key = $project['project_name'] . $page['path'];
                        if (!isset($all_pages[$key])) {
                            $all_pages[$key] = array(
                                'project' => $project['project_name'],
                                'path' => $page['path'],
                                'visits' => 0
                            );
                        }
                        $all_pages[$key]['visits'] += $page['visits'];
                    }
                }
                uasort($all_pages, function($a, $b) {
                    return $b['visits'] - $a['visits'];
                });
                $all_pages = array_slice($all_pages, 0, 10);
                
                $max_visits = !empty($all_pages) ? max(array_column($all_pages, 'visits')) : 1;
                
                foreach ($all_pages as $page):
                    $percentage = ($page['visits'] / $max_visits) * 100;
                ?>
                <div class="trico-data-item">
                    <div class="trico-data-info">
                        <span class="trico-data-primary"><?php echo esc_html($page['path']); ?></span>
                        <span class="trico-data-secondary"><?php echo esc_html($page['project']); ?></span>
                    </div>
                    <div class="trico-data-bar-container">
                        <div class="trico-data-bar" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                    </div>
                    <span class="trico-data-value"><?php echo number_format($page['visits']); ?></span>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($all_pages)): ?>
                <p class="trico-no-data"><?php _e('No data available', 'trico-ai'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Top Countries -->
        <div class="trico-card">
            <h3><?php _e('Top Countries', 'trico-ai'); ?></h3>
            <div class="trico-data-list">
                <?php 
                $max_country = !empty($stats['top_countries']) 
                    ? max(array_column($stats['top_countries'], 'visits')) 
                    : 1;
                
                foreach ($stats['top_countries'] as $country):
                    $percentage = ($country['visits'] / $max_country) * 100;
                    $flag = $this->get_country_flag($country['country']);
                ?>
                <div class="trico-data-item">
                    <div class="trico-data-info">
                        <span class="trico-data-primary">
                            <?php echo $flag; ?> <?php echo esc_html($country['country']); ?>
                        </span>
                    </div>
                    <div class="trico-data-bar-container">
                        <div class="trico-data-bar trico-bar-blue" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                    </div>
                    <span class="trico-data-value"><?php echo number_format($country['visits']); ?></span>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($stats['top_countries'])): ?>
                <p class="trico-no-data"><?php _e('No data available', 'trico-ai'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Devices -->
        <div class="trico-card">
            <h3><?php _e('Devices', 'trico-ai'); ?></h3>
            <div class="trico-devices-chart">
                <canvas id="trico-devices-chart"></canvas>
            </div>
            <div class="trico-devices-legend">
                <?php 
                $device_icons = array(
                    'desktop' => '💻',
                    'mobile' => '📱',
                    'tablet' => '📟',
                    'other' => '🖥️'
                );
                $total_devices = array_sum(array_column($stats['devices'], 'visits'));
                
                foreach ($stats['devices'] as $device):
                    $type = strtolower($device['type']);
                    $icon = $device_icons[$type] ?? '🖥️';
                    $pct = $total_devices > 0 ? round(($device['visits'] / $total_devices) * 100, 1) : 0;
                ?>
                <div class="trico-device-item">
                    <span class="trico-device-icon"><?php echo $icon; ?></span>
                    <span class="trico-device-name"><?php echo esc_html(ucfirst($device['type'])); ?></span>
                    <span class="trico-device-pct"><?php echo $pct; ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Per-Project Stats -->
    <div class="trico-section">
        <h2><?php _e('Per-Site Analytics', 'trico-ai'); ?></h2>
        <div class="trico-projects-analytics-grid">
            <?php foreach ($stats['projects'] as $project): ?>
            <div class="trico-card trico-project-analytics-card">
                <div class="trico-project-analytics-header">
                    <h4><?php echo esc_html($project['project_name']); ?></h4>
                    <a href="<?php echo esc_url($project['url']); ?>" target="_blank" class="trico-external-link">
                        🔗
                    </a>
                </div>
                <div class="trico-project-analytics-stats">
                    <div class="trico-mini-stat">
                        <span class="value"><?php echo number_format($project['analytics']['total_visits'] ?? 0); ?></span>
                        <span class="label"><?php _e('Visitors', 'trico-ai'); ?></span>
                    </div>
                    <div class="trico-mini-stat">
                        <span class="value"><?php echo number_format($project['analytics']['total_pageviews'] ?? 0); ?></span>
                        <span class="label"><?php _e('Views', 'trico-ai'); ?></span>
                    </div>
                </div>
                <div class="trico-project-analytics-url">
                    <code><?php echo esc_html(parse_url($project['url'], PHP_URL_HOST)); ?></code>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<?php
// Helper function for country flags
function get_country_flag($country) {
    $flags = array(
        'Indonesia' => '🇮🇩',
        'United States' => '🇺🇸',
        'Singapore' => '🇸🇬',
        'Malaysia' => '🇲🇾',
        'Australia' => '🇦🇺',
        'Japan' => '🇯🇵',
        'United Kingdom' => '🇬🇧',
        'Germany' => '🇩🇪',
        'India' => '🇮🇳',
        'Netherlands' => '🇳🇱'
    );
    return $flags[$country] ?? '🌍';
}
?>

<style>
/* Stats Grid */
.trico-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.trico-stat-card {
    background: #fff;
    border: 1px solid var(--trico-border);
    border-radius: var(--trico-radius);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.trico-stat-primary {
    background: linear-gradient(135deg, var(--trico-primary), var(--trico-secondary));
    border: none;
    color: #fff;
}

.trico-stat-primary .trico-stat-icon {
    background: rgba(255,255,255,0.2);
}

.trico-stat-icon {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--trico-light);
    border-radius: 12px;
    font-size: 1.5rem;
}

.trico-stat-content {
    display: flex;
    flex-direction: column;
}

.trico-stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1.2;
}

.trico-stat-label {
    font-size: 0.875rem;
    opacity: 0.8;
}

/* Charts */
.trico-charts-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.trico-chart-card {
    padding: 1.5rem;
}

.trico-chart-card h3 {
    margin: 0 0 1rem;
    font-size: 1rem;
    font-weight: 600;
}

.trico-chart-container {
    height: 300px;
}

/* Data Lists */
.trico-data-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.trico-data-row .trico-card {
    padding: 1.5rem;
}

.trico-data-row .trico-card h3 {
    margin: 0 0 1rem;
    font-size: 1rem;
    font-weight: 600;
}

.trico-data-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.trico-data-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.trico-data-info {
    flex: 1;
    min-width: 0;
}

.trico-data-primary {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.trico-data-secondary {
    display: block;
    font-size: 0.75rem;
    color: var(--trico-gray);
}

.trico-data-bar-container {
    width: 80px;
    height: 6px;
    background: var(--trico-light);
    border-radius: 3px;
    overflow: hidden;
}

.trico-data-bar {
    height: 100%;
    background: var(--trico-primary);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.trico-bar-blue {
    background: #3b82f6;
}

.trico-data-value {
    font-size: 0.875rem;
    font-weight: 600;
    min-width: 50px;
    text-align: right;
}

/* Devices Chart */
.trico-devices-chart {
    height: 180px;
    margin-bottom: 1rem;
}

.trico-devices-legend {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.trico-device-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.trico-device-icon {
    font-size: 1rem;
}

.trico-device-name {
    flex: 1;
}

.trico-device-pct {
    font-weight: 600;
}

/* Per-Project Grid */
.trico-projects-analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.trico-project-analytics-card {
    padding: 1.25rem;
}

.trico-project-analytics-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.trico-project-analytics-header h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.trico-external-link {
    text-decoration: none;
}

.trico-project-analytics-stats {
    display: flex;
    gap: 2rem;
    margin-bottom: 1rem;
}

.trico-mini-stat {
    display: flex;
    flex-direction: column;
}

.trico-mini-stat .value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--trico-primary);
}

.trico-mini-stat .label {
    font-size: 0.75rem;
    color: var(--trico-gray);
}

.trico-project-analytics-url code {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    background: var(--trico-light);
    border-radius: 0.25rem;
}

.trico-no-data {
    color: var(--trico-gray);
    font-size: 0.875rem;
    text-align: center;
    padding: 2rem 0;
    margin: 0;
}

/* Responsive */
@media (max-width: 1200px) {
    .trico-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .trico-data-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .trico-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .trico-header {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
jQuery(document).ready(function($) {
    // Period selector
    $('#synalytics-period').on('change', function() {
        var period = $(this).val();
        window.location.href = '<?php echo admin_url('admin.php?page=trico-synalytics'); ?>&period=' + period;
    });
    
    <?php if (!empty($stats['projects'])): ?>
    
    // Visitors Chart
    var visitorsCtx = document.getElementById('trico-visitors-chart');
    
    if (visitorsCtx) {
        // Aggregate daily data
        var dailyData = {};
        
        <?php foreach ($stats['projects'] as $project): ?>
        <?php foreach ($project['analytics']['daily'] ?? array() as $date => $data): ?>
        if (!dailyData['<?php echo esc_js($date); ?>']) {
            dailyData['<?php echo esc_js($date); ?>'] = { visits: 0, pageviews: 0 };
        }
        dailyData['<?php echo esc_js($date); ?>'].visits += <?php echo intval($data['visits']); ?>;
        dailyData['<?php echo esc_js($date); ?>'].pageviews += <?php echo intval($data['pageviews']); ?>;
        <?php endforeach; ?>
        <?php endforeach; ?>
        
        var labels = Object.keys(dailyData).sort();
        var visitsData = labels.map(function(date) { return dailyData[date].visits; });
        var pageviewsData = labels.map(function(date) { return dailyData[date].pageviews; });
        
        new Chart(visitorsCtx, {
            type: 'line',
            data: {
                labels: labels.map(function(d) { 
                    return new Date(d).toLocaleDateString('id-ID', { month: 'short', day: 'numeric' }); 
                }),
                datasets: [
                    {
                        label: '<?php _e('Visitors', 'trico-ai'); ?>',
                        data: visitsData,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: '<?php _e('Page Views', 'trico-ai'); ?>',
                        data: pageviewsData,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Devices Doughnut Chart
    var devicesCtx = document.getElementById('trico-devices-chart');
    
    if (devicesCtx) {
        var deviceLabels = [];
        var deviceData = [];
        var deviceColors = ['#6366f1', '#8b5cf6', '#06b6d4', '#64748b'];
        
        <?php foreach ($stats['devices'] as $index => $device): ?>
        deviceLabels.push('<?php echo esc_js(ucfirst($device['type'])); ?>');
        deviceData.push(<?php echo intval($device['visits']); ?>);
        <?php endforeach; ?>
        
        new Chart(devicesCtx, {
            type: 'doughnut',
            data: {
                labels: deviceLabels,
                datasets: [{
                    data: deviceData,
                    backgroundColor: deviceColors.slice(0, deviceLabels.length),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '70%'
            }
        });
    }
    
    <?php endif; ?>
});
</script>
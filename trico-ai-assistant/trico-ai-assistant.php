<?php
/**
 * Plugin Name: Trico AI Assistant
 * Plugin URI: https://synpages.synavy.com
 * Description: AI-powered website generator with WordPress Block Editor integration. Generate modern, stunning websites with one prompt.
 * Version: 1.0.0
 * Author: Synavy Team
 * Author URI: https://synavy.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: trico-ai
 * Domain Path: /languages
 * Network: true
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

/**
 * Plugin Constants
 */
define('TRICO_VERSION', '1.0.0');
define('TRICO_PLUGIN_FILE', __FILE__);
define('TRICO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TRICO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TRICO_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Environment Constants (from HF Secrets)
 */
define('TRICO_DEFAULT_DOMAIN', getenv('TRICO_DOMAIN') ?: 'synpages.synavy.com');
define('TRICO_CF_API_TOKEN', getenv('CF_API_TOKEN') ?: '');
define('TRICO_CF_ACCOUNT_ID', getenv('CF_ACCOUNT_ID') ?: '');
define('TRICO_B2_KEY_ID', getenv('B2_KEY_ID') ?: '');
define('TRICO_B2_APP_KEY', getenv('B2_APP_KEY') ?: '');
define('TRICO_B2_BUCKET_ID', getenv('B2_BUCKET_ID') ?: '');
define('TRICO_B2_BUCKET_NAME', getenv('B2_BUCKET_NAME') ?: '');

/**
 * Autoloader
 */
spl_autoload_register(function ($class) {
    $prefix = 'Trico_';
    
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    
    $class_name = str_replace($prefix, '', $class);
    $class_name = strtolower(str_replace('_', '-', $class_name));
    
    $paths = array(
        TRICO_PLUGIN_DIR . 'includes/class-trico-' . $class_name . '.php',
        TRICO_PLUGIN_DIR . 'admin/class-trico-' . $class_name . '.php',
    );
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

/**
 * Main Plugin Class
 */
final class Trico_AI_Assistant {
    
    private static $instance = null;
    
    public $core;
    public $api_manager;
    public $database;
    public $admin;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        register_activation_hook(TRICO_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(TRICO_PLUGIN_FILE, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'), 0);
        add_action('init', array($this, 'load_textdomain'));
    }
    
    public function init() {
        $this->includes();
        $this->init_components();
    }
    
    private function includes() {
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-core.php';
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-api-manager.php';
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-database.php';
        
        if (is_admin() || is_network_admin()) {
            require_once TRICO_PLUGIN_DIR . 'admin/class-trico-admin.php';
            require_once TRICO_PLUGIN_DIR . 'admin/class-trico-admin-ajax.php';
        }
    }
    
    private function init_components() {
        $this->core = new Trico_Core();
        $this->api_manager = new Trico_API_Manager();
        $this->database = new Trico_Database();
        
        if (is_admin() || is_network_admin()) {
            $this->admin = new Trico_Admin();
            new Trico_Admin_Ajax();
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'trico-ai',
            false,
            dirname(TRICO_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    public function activate($network_wide) {
        if (is_multisite() && $network_wide) {
            $sites = get_sites(array('number' => 0));
            foreach ($sites as $site) {
                switch_to_blog($site->blog_id);
                $this->run_activation();
                restore_current_blog();
            }
        } else {
            $this->run_activation();
        }
    }
    
    private function run_activation() {
        require_once TRICO_PLUGIN_DIR . 'includes/class-trico-database.php';
        $database = new Trico_Database();
        $database->create_tables();
        
        add_option('trico_version', TRICO_VERSION);
        add_option('trico_installed_at', current_time('mysql'));
        
        flush_rewrite_rules();
    }
    
    public function deactivate($network_wide) {
        flush_rewrite_rules();
    }
    
    public function __clone() {
        _doing_it_wrong(__FUNCTION__, 'Cloning is forbidden.', TRICO_VERSION);
    }
    
    public function __wakeup() {
        _doing_it_wrong(__FUNCTION__, 'Unserializing is forbidden.', TRICO_VERSION);
    }
}

/**
 * Returns main instance
 */
function trico() {
    return Trico_AI_Assistant::instance();
}

// Initialize
trico();
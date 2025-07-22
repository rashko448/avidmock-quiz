<?php
/**
 * Plugin Name: AvidMock SAT
 * Plugin URI: https://avidmock.com/sat-quiz
 * Description: Professional SAT practice quiz system. Create interactive mathematics quizzes with rich content areas, real-time explanations, and comprehensive analytics.
 * Version: 1.0.0
 * Author: AvidMock Team
 * Author URI: https://avidmock.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: avidmock-sat
 * Domain Path: /languages
 * Requires at least: 5.5
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Elementor tested up to: 3.18
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// Define plugin constants
define('AVIDMOCK_SAT_VERSION', '1.0.0');
define('AVIDMOCK_SAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AVIDMOCK_SAT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AVIDMOCK_SAT_PLUGIN_FILE', __FILE__);
define('AVIDMOCK_SAT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('AVIDMOCK_SAT_TEXT_DOMAIN', 'avidmock-sat');
define('AVIDMOCK_SAT_DB_VERSION', '1.0.0');

// Define asset URLs
define('AVIDMOCK_SAT_ASSETS_URL', AVIDMOCK_SAT_PLUGIN_URL . 'assets/');
define('AVIDMOCK_SAT_CSS_URL', AVIDMOCK_SAT_ASSETS_URL . 'css/');
define('AVIDMOCK_SAT_JS_URL', AVIDMOCK_SAT_ASSETS_URL . 'js/');
define('AVIDMOCK_SAT_IMAGES_URL', AVIDMOCK_SAT_ASSETS_URL . 'images/');

// Define database table names
global $wpdb;
define('AVIDMOCK_SAT_TABLE_CATEGORIES', $wpdb->prefix . 'avidmock_quiz_categories');
define('AVIDMOCK_SAT_TABLE_QUESTIONS', $wpdb->prefix . 'avidmock_quiz_questions');
define('AVIDMOCK_SAT_TABLE_OPTIONS', $wpdb->prefix . 'avidmock_quiz_answer_options');
define('AVIDMOCK_SAT_TABLE_SESSIONS', $wpdb->prefix . 'avidmock_quiz_sessions');
define('AVIDMOCK_SAT_TABLE_ANSWERS', $wpdb->prefix . 'avidmock_quiz_user_answers');
define('AVIDMOCK_SAT_TABLE_STATS', $wpdb->prefix . 'avidmock_quiz_user_stats');
define('AVIDMOCK_SAT_TABLE_SETTINGS', $wpdb->prefix . 'avidmock_quiz_settings');

/**
 * Main AvidMock SAT Plugin Class
 */
final class AvidMock_SAT {
    
    private static $instance = null;
    public $version;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->version = AVIDMOCK_SAT_VERSION;
        
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        // Core classes
        require_once AVIDMOCK_SAT_PLUGIN_PATH . 'includes/class-activator.php';
        require_once AVIDMOCK_SAT_PLUGIN_PATH . 'includes/class-deactivator.php';
        require_once AVIDMOCK_SAT_PLUGIN_PATH . 'includes/class-i18n.php';
        
        // Database
        require_once AVIDMOCK_SAT_PLUGIN_PATH . 'database/class-database-manager.php';
        
        // Admin
        if (is_admin()) {
            require_once AVIDMOCK_SAT_PLUGIN_PATH . 'admin/class-admin.php';
        }
        
        // Public
        require_once AVIDMOCK_SAT_PLUGIN_PATH . 'public/class-public.php';
        require_once AVIDMOCK_SAT_PLUGIN_PATH . 'public/class-quiz-handler.php';
        
        // Elementor
        require_once AVIDMOCK_SAT_PLUGIN_PATH . 'elementor/class-elementor-integration.php';
        
        // AJAX
        require_once AVIDMOCK_SAT_PLUGIN_PATH . 'ajax/class-quiz-ajax.php';
        
        // API
        require_once AVIDMOCK_SAT_PLUGIN_PATH . 'api/class-quiz-api.php';
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Check if Elementor is loaded
        add_action('plugins_loaded', array($this, 'check_elementor'));
    }
    
    public function init() {
        // Initialize components
        new AvidMock_SAT_Database_Manager();
        new AvidMock_SAT_Public();
        new AvidMock_SAT_Quiz_Ajax();
        new AvidMock_SAT_Quiz_API();
        
        if (is_admin()) {
            new AvidMock_SAT_Admin();
        }
        
        do_action('avidmock_sat_loaded');
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            AVIDMOCK_SAT_TEXT_DOMAIN,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
    
    public function check_elementor() {
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', array($this, 'elementor_missing_notice'));
            return;
        }
        
        // Initialize Elementor integration
        new AvidMock_SAT_Elementor_Integration();
    }
    
    public function elementor_missing_notice() {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php _e('AvidMock SAT', AVIDMOCK_SAT_TEXT_DOMAIN); ?></strong>
                <?php _e('requires Elementor to be installed and activated.', AVIDMOCK_SAT_TEXT_DOMAIN); ?>
                <a href="<?php echo esc_url(admin_url('plugin-install.php?s=elementor&tab=search&type=term')); ?>">
                    <?php _e('Install Elementor now', AVIDMOCK_SAT_TEXT_DOMAIN); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    public function activate() {
        AvidMock_SAT_Activator::activate();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        AvidMock_SAT_Deactivator::deactivate();
        flush_rewrite_rules();
    }
    
    public function get_version() {
        return $this->version;
    }
}

// Initialize the plugin
function avidmock_sat() {
    return AvidMock_SAT::instance();
}

// Start the plugin
add_action('plugins_loaded', 'avidmock_sat', 10);

// Helper functions
function avidmock_sat_get_option($option_name, $default = false) {
    return get_option('avidmock_sat_' . $option_name, $default);
}

function avidmock_sat_update_option($option_name, $value) {
    return update_option('avidmock_sat_' . $option_name, $value);
}

function avidmock_sat_is_elementor_active() {
    return did_action('elementor/loaded');
}
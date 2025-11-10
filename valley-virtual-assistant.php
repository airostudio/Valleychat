<?php
/**
 * Plugin Name: Valley Virtual Assistant
 * Plugin URI: https://valleyofthedolls.com.au
 * Description: AI-powered virtual assistant for WooCommerce stores - handles product recommendations, cart management, and customer service
 * Version: 1.0.1
 * Author: Valley of the Dolls
 * Author URI: https://valleyofthedolls.com.au
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: valley-virtual-assistant
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VVA_VERSION', '1.0.1');
define('VVA_PLUGIN_FILE', __FILE__);
define('VVA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VVA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VVA_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Valley Virtual Assistant Class
 */
final class Valley_Virtual_Assistant {

    /**
     * The single instance of the class
     */
    private static $instance = null;

    /**
     * Main Instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->includes();
    }

    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('before_woocommerce_init', array($this, 'declare_woocommerce_compatibility'));
        add_action('plugins_loaded', array($this, 'check_dependencies'));
        add_action('woocommerce_init', array($this, 'woocommerce_init'));
        add_action('init', array($this, 'init'), 0);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // WooCommerce cart fragments support
        add_filter('woocommerce_add_to_cart_fragments', array($this, 'cart_fragments'));
    }

    /**
     * Declare WooCommerce HPOS compatibility
     */
    public function declare_woocommerce_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            // Declare HPOS compatibility
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);

            // Declare cart/checkout blocks compatibility
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        }
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once VVA_PLUGIN_DIR . 'includes/class-vva-installer.php';
        require_once VVA_PLUGIN_DIR . 'includes/class-vva-ajax.php';
        require_once VVA_PLUGIN_DIR . 'includes/class-vva-api.php';
        require_once VVA_PLUGIN_DIR . 'includes/class-vva-assistant.php';
        require_once VVA_PLUGIN_DIR . 'includes/admin/class-vva-analytics.php';
        require_once VVA_PLUGIN_DIR . 'includes/class-vva-woocommerce.php';
        require_once VVA_PLUGIN_DIR . 'includes/class-vva-product-knowledge.php';
        require_once VVA_PLUGIN_DIR . 'includes/class-vva-customer-service.php';
        require_once VVA_PLUGIN_DIR . 'includes/class-vva-chat-widget.php';
        require_once VVA_PLUGIN_DIR . 'includes/class-vva-privacy.php';

        // Admin classes
        if (is_admin()) {
            require_once VVA_PLUGIN_DIR . 'includes/admin/class-vva-admin.php';
            require_once VVA_PLUGIN_DIR . 'includes/admin/class-vva-settings.php';
        }
    }

    /**
     * Check if WooCommerce is active
     */
    public function check_dependencies() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return false;
        }
        return true;
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('Valley Virtual Assistant requires WooCommerce to be installed and active.', 'valley-virtual-assistant'); ?></p>
        </div>
        <?php
    }

    /**
     * WooCommerce initialization
     */
    public function woocommerce_init() {
        // Ensure WooCommerce session is started for cart functionality
        if (!is_admin() && function_exists('WC') && WC()->session) {
            if (!WC()->session->has_session()) {
                WC()->session->set_customer_session_cookie(true);
            }
        }
    }

    /**
     * Add cart fragments for AJAX cart updates
     */
    public function cart_fragments($fragments) {
        // Only add fragments if WooCommerce is properly initialized
        if (function_exists('WC') && WC()->cart) {
            $fragments['vva_cart_count'] = WC()->cart->get_cart_contents_count();
            $fragments['vva_cart_total'] = WC()->cart->get_cart_total();
        }

        return $fragments;
    }

    /**
     * Init the plugin
     */
    public function init() {
        // Load plugin text domain
        load_plugin_textdomain('valley-virtual-assistant', false, dirname(VVA_PLUGIN_BASENAME) . '/languages');

        // Initialize components
        VVA_AJAX::instance();
        VVA_API::instance();
        VVA_Assistant::instance();
        VVA_WooCommerce::instance();
        VVA_Product_Knowledge::instance();
        VVA_Customer_Service::instance();
        VVA_Chat_Widget::instance();
        VVA_Privacy::instance();

        if (is_admin()) {
            VVA_Admin::instance();
            VVA_Settings::instance();
            VVA_Analytics::instance();
        }
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style('vva-frontend', VVA_PLUGIN_URL . 'assets/css/frontend.css', array(), VVA_VERSION);
        wp_enqueue_script('vva-frontend', VVA_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), VVA_VERSION, true);

        // Localize script
        wp_localize_script('vva-frontend', 'vvaData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vva-nonce'),
            'assistantName' => get_option('vva_assistant_name', 'Sophie'),
            'assistantAvatar' => get_option('vva_assistant_avatar', VVA_PLUGIN_URL . 'assets/images/sophie-avatar.svg'),
            'primaryColor' => get_option('vva_primary_color', '#e91e63'),
            'position' => get_option('vva_widget_position', 'bottom-right'),
            'welcomeMessage' => get_option('vva_welcome_message', 'Hi! I\'m Sophie, your personal shopping assistant. How can I help you today?'),
        ));

        // Ensure viewport meta tag for mobile
        add_action('wp_head', function() {
            echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">' . "\n";
        }, 1);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'valley-virtual-assistant') === false) {
            return;
        }

        // Enqueue WordPress media library if needed
        wp_enqueue_media();

        // Enqueue color picker
        wp_enqueue_style('wp-color-picker');

        // Load select2 if available (for enhanced selects)
        if (wp_script_is('select2', 'registered')) {
            wp_enqueue_script('select2');
            wp_enqueue_style('select2');
        }

        // Enqueue our admin styles and scripts
        wp_enqueue_style('vva-admin', VVA_PLUGIN_URL . 'assets/css/admin.css', array(), VVA_VERSION);

        // Add proper dependencies including wp-color-picker
        $dependencies = array('jquery', 'wp-color-picker');
        if (wp_script_is('select2', 'registered')) {
            $dependencies[] = 'select2';
        }

        wp_enqueue_script('vva-admin', VVA_PLUGIN_URL . 'assets/js/admin.js', $dependencies, VVA_VERSION, true);

        wp_localize_script('vva-admin', 'vvaAdminData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vva-admin-nonce'),
        ));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        require_once VVA_PLUGIN_DIR . 'includes/class-vva-installer.php';
        VVA_Installer::activate();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Cleanup if needed
    }
}

/**
 * Returns the main instance of Valley Virtual Assistant
 */
function VVA() {
    return Valley_Virtual_Assistant::instance();
}

// Initialize the plugin
VVA();

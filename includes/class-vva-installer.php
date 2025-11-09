<?php
/**
 * Valley Virtual Assistant Installer
 *
 * @package Valley_Virtual_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VVA_Installer Class
 */
class VVA_Installer {

    /**
     * Install/Update
     */
    public static function activate() {
        self::create_tables();
        self::create_default_options();
        self::create_pages();

        // Update version
        update_option('vva_version', VVA_VERSION);
        update_option('vva_activated_time', time());

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Conversations table
        $conversations_table = $wpdb->prefix . 'vva_conversations';
        $conversations_sql = "CREATE TABLE IF NOT EXISTS $conversations_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            customer_email varchar(255) DEFAULT NULL,
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            ended_at datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY customer_email (customer_email)
        ) $charset_collate;";

        // Messages table
        $messages_table = $wpdb->prefix . 'vva_messages';
        $messages_sql = "CREATE TABLE IF NOT EXISTS $messages_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) NOT NULL,
            role varchar(20) NOT NULL,
            content longtext NOT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY conversation_id (conversation_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Product recommendations table
        $recommendations_table = $wpdb->prefix . 'vva_recommendations';
        $recommendations_sql = "CREATE TABLE IF NOT EXISTS $recommendations_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            score decimal(5,2) DEFAULT 0.00,
            reason text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY conversation_id (conversation_id),
            KEY product_id (product_id)
        ) $charset_collate;";

        // Analytics table
        $analytics_table = $wpdb->prefix . 'vva_analytics';
        $analytics_sql = "CREATE TABLE IF NOT EXISTS $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_data longtext DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($conversations_sql);
        dbDelta($messages_sql);
        dbDelta($recommendations_sql);
        dbDelta($analytics_sql);
    }

    /**
     * Create default options
     */
    private static function create_default_options() {
        $defaults = array(
            // Assistant personality
            'vva_assistant_name' => 'Sophie',
            'vva_assistant_personality' => 'friendly_professional',
            'vva_assistant_avatar' => VVA_PLUGIN_URL . 'assets/images/sophie-avatar.svg',
            'vva_welcome_message' => 'Hi! I\'m Sophie, your personal shopping assistant. I\'m here to help you discover amazing products that are perfect for you. What can I help you find today?',

            // AI Settings
            'vva_ai_provider' => 'anthropic',
            'vva_anthropic_api_key' => '',
            'vva_anthropic_model' => 'claude-3-5-sonnet-20240620',
            'vva_max_tokens' => 4096,
            'vva_temperature' => 0.7,

            // Widget Settings
            'vva_widget_enabled' => true,
            'vva_widget_position' => 'bottom-right',
            'vva_primary_color' => '#e91e63',
            'vva_secondary_color' => '#f8bbd0',

            // Privacy Settings
            'vva_data_retention_days' => 90,
            'vva_anonymous_mode' => false,
            'vva_encryption_enabled' => true,

            // Features
            'vva_order_tracking' => true,
            'vva_product_recommendations' => true,
            'vva_customer_service' => true,
            'vva_proactive_assistance' => true,

            // Advanced
            'vva_debug_mode' => false,
            'vva_log_conversations' => true,
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Create plugin pages
     */
    private static function create_pages() {
        // Create privacy policy page if needed
        $privacy_page_id = get_option('vva_privacy_page_id');

        if (!$privacy_page_id || !get_post($privacy_page_id)) {
            $page_id = wp_insert_post(array(
                'post_title' => 'Virtual Assistant Privacy Policy',
                'post_content' => self::get_privacy_policy_content(),
                'post_status' => 'draft',
                'post_type' => 'page',
                'post_author' => get_current_user_id(),
            ));

            if ($page_id && !is_wp_error($page_id)) {
                update_option('vva_privacy_page_id', $page_id);
            }
        }
    }

    /**
     * Get default privacy policy content
     */
    private static function get_privacy_policy_content() {
        return '
<h2>Virtual Assistant Privacy Policy</h2>

<h3>Information Collection</h3>
<p>Our virtual assistant collects and processes the following information:</p>
<ul>
    <li>Chat conversations and messages</li>
    <li>Product browsing and search history</li>
    <li>Order information (when logged in)</li>
    <li>Basic session data for improving service</li>
</ul>

<h3>Data Usage</h3>
<p>We use this information to:</p>
<ul>
    <li>Provide personalized product recommendations</li>
    <li>Answer questions about your orders</li>
    <li>Improve our virtual assistant service</li>
    <li>Ensure complete discretion in all interactions</li>
</ul>

<h3>Data Protection</h3>
<p>Your privacy is our top priority:</p>
<ul>
    <li>All conversations are encrypted</li>
    <li>Data is stored securely and retained for a limited time</li>
    <li>We never share your information with third parties</li>
    <li>You can request deletion of your data at any time</li>
</ul>

<h3>Your Rights</h3>
<p>You have the right to:</p>
<ul>
    <li>Access your conversation history</li>
    <li>Request deletion of your data</li>
    <li>Opt-out of data collection</li>
    <li>Export your data</li>
</ul>
        ';
    }
}

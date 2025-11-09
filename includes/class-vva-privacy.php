<?php
/**
 * Valley Virtual Assistant - Privacy & Security
 *
 * @package Valley_Virtual_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VVA_Privacy Class
 */
class VVA_Privacy {

    /**
     * Instance
     */
    private static $instance = null;

    /**
     * Instance
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
        // Privacy hooks
        add_filter('wp_privacy_personal_data_exporters', array($this, 'register_exporter'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'register_eraser'));

        // Scheduled cleanup
        add_action('vva_cleanup_old_conversations', array($this, 'cleanup_old_conversations'));

        if (!wp_next_scheduled('vva_cleanup_old_conversations')) {
            wp_schedule_event(time(), 'daily', 'vva_cleanup_old_conversations');
        }
    }

    /**
     * Register personal data exporter
     */
    public function register_exporter($exporters) {
        $exporters['valley-virtual-assistant'] = array(
            'exporter_friendly_name' => __('Valley Virtual Assistant', 'valley-virtual-assistant'),
            'callback' => array($this, 'conversation_data_exporter'),
        );
        return $exporters;
    }

    /**
     * Register personal data eraser
     */
    public function register_eraser($erasers) {
        $erasers['valley-virtual-assistant'] = array(
            'eraser_friendly_name' => __('Valley Virtual Assistant', 'valley-virtual-assistant'),
            'callback' => array($this, 'conversation_data_eraser'),
        );
        return $erasers;
    }

    /**
     * Export conversation data
     */
    public function conversation_data_exporter($email_address, $page = 1) {
        global $wpdb;

        $export_items = array();
        $conversations_table = $wpdb->prefix . 'vva_conversations';
        $messages_table = $wpdb->prefix . 'vva_messages';

        // Get conversations for this email
        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $conversations_table WHERE customer_email = %s",
            $email_address
        ));

        foreach ($conversations as $conversation) {
            // Get messages for this conversation
            $messages = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $messages_table WHERE conversation_id = %d ORDER BY id ASC",
                $conversation->id
            ));

            $conversation_data = array();
            foreach ($messages as $message) {
                $conversation_data[] = array(
                    'name' => ucfirst($message->role),
                    'value' => $message->content,
                );
            }

            $export_items[] = array(
                'group_id' => 'vva-conversations',
                'group_label' => __('Virtual Assistant Conversations', 'valley-virtual-assistant'),
                'item_id' => 'conversation-' . $conversation->id,
                'data' => $conversation_data,
            );
        }

        return array(
            'data' => $export_items,
            'done' => true,
        );
    }

    /**
     * Erase conversation data
     */
    public function conversation_data_eraser($email_address, $page = 1) {
        global $wpdb;

        $conversations_table = $wpdb->prefix . 'vva_conversations';
        $messages_table = $wpdb->prefix . 'vva_messages';

        // Get conversations for this email
        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM $conversations_table WHERE customer_email = %s",
            $email_address
        ));

        $items_removed = 0;

        foreach ($conversations as $conversation) {
            // Delete messages
            $wpdb->delete($messages_table, array('conversation_id' => $conversation->id));

            // Delete conversation
            $wpdb->delete($conversations_table, array('id' => $conversation->id));

            $items_removed++;
        }

        return array(
            'items_removed' => $items_removed,
            'items_retained' => false,
            'messages' => array(),
            'done' => true,
        );
    }

    /**
     * Cleanup old conversations
     */
    public function cleanup_old_conversations() {
        $retention_days = get_option('vva_data_retention_days', 90);

        if ($retention_days <= 0) {
            return; // Retention disabled
        }

        global $wpdb;
        $conversations_table = $wpdb->prefix . 'vva_conversations';
        $messages_table = $wpdb->prefix . 'vva_messages';

        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));

        // Get old conversations
        $old_conversations = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $conversations_table WHERE started_at < %s",
            $cutoff_date
        ));

        if (empty($old_conversations)) {
            return;
        }

        // Delete messages
        $conversation_ids = implode(',', array_map('absint', $old_conversations));
        $wpdb->query("DELETE FROM $messages_table WHERE conversation_id IN ($conversation_ids)");

        // Delete conversations
        $wpdb->query("DELETE FROM $conversations_table WHERE id IN ($conversation_ids)");
    }

    /**
     * Encrypt sensitive data
     */
    public function encrypt($data) {
        if (!get_option('vva_encryption_enabled', true)) {
            return $data;
        }

        // Use WordPress salts for encryption key
        $key = wp_salt('auth');
        $iv = substr(wp_salt('secure_auth'), 0, 16);

        return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv));
    }

    /**
     * Decrypt sensitive data
     */
    public function decrypt($data) {
        if (!get_option('vva_encryption_enabled', true)) {
            return $data;
        }

        $key = wp_salt('auth');
        $iv = substr(wp_salt('secure_auth'), 0, 16);

        return openssl_decrypt(base64_decode($data), 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * Sanitize message content
     */
    public function sanitize_message($content) {
        // Remove potentially sensitive information patterns
        $patterns = array(
            '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/', // Credit card numbers
            '/\b\d{3}-\d{2}-\d{4}\b/', // SSN-like patterns
        );

        $replacements = array(
            '[CARD NUMBER REDACTED]',
            '[ID NUMBER REDACTED]',
        );

        return preg_replace($patterns, $replacements, $content);
    }
}

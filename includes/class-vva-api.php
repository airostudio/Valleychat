<?php
/**
 * Valley Virtual Assistant - REST API
 *
 * @package Valley_Virtual_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VVA_API Class
 */
class VVA_API {

    /**
     * Instance
     */
    private static $instance = null;

    /**
     * Namespace
     */
    private $namespace = 'vva/v1';

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
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Chat endpoint
        register_rest_route($this->namespace, '/chat', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_chat'),
            'permission_callback' => '__return_true',
        ));

        // Product search endpoint
        register_rest_route($this->namespace, '/products/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_products'),
            'permission_callback' => '__return_true',
        ));

        // Orders endpoint (authenticated)
        register_rest_route($this->namespace, '/orders', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_orders'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        // Conversation history (authenticated)
        register_rest_route($this->namespace, '/conversations/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_conversation'),
            'permission_callback' => array($this, 'check_auth'),
        ));
    }

    /**
     * Handle chat message
     */
    public function handle_chat($request) {
        $message = $request->get_param('message');
        $conversation_id = $request->get_param('conversation_id');

        if (empty($message)) {
            return new WP_Error('empty_message', __('Message cannot be empty.', 'valley-virtual-assistant'), array('status' => 400));
        }

        // Implementation similar to AJAX handler
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'API endpoint for chat',
        ));
    }

    /**
     * Search products
     */
    public function search_products($request) {
        $query = $request->get_param('q');
        $category = $request->get_param('category');
        $limit = $request->get_param('limit') ?: 10;

        $knowledge = VVA_Product_Knowledge::instance();
        $results = $knowledge->search_products(array(
            'query' => $query,
            'category' => $category,
            'limit' => $limit,
        ));

        return rest_ensure_response($results);
    }

    /**
     * Get customer orders
     */
    public function get_orders($request) {
        $customer_service = VVA_Customer_Service::instance();
        $orders = $customer_service->get_customer_orders(array());

        return rest_ensure_response($orders);
    }

    /**
     * Get conversation
     */
    public function get_conversation($request) {
        $conversation_id = $request->get_param('id');

        // Get conversation from database
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'vva_conversations';
        $messages_table = $wpdb->prefix . 'vva_messages';

        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $conversations_table WHERE id = %d",
            $conversation_id
        ));

        if (!$conversation) {
            return new WP_Error('not_found', __('Conversation not found.', 'valley-virtual-assistant'), array('status' => 404));
        }

        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $messages_table WHERE conversation_id = %d ORDER BY id ASC",
            $conversation_id
        ), ARRAY_A);

        return rest_ensure_response(array(
            'conversation' => $conversation,
            'messages' => $messages,
        ));
    }

    /**
     * Check if user is authenticated
     */
    public function check_auth($request) {
        return is_user_logged_in();
    }
}

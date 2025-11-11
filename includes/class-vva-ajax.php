<?php
/**
 * Valley Virtual Assistant - AJAX Handlers
 *
 * @package Valley_Virtual_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VVA_AJAX Class
 */
class VVA_AJAX {

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
        // Public AJAX actions (logged in and out)
        add_action('wp_ajax_vva_send_message', array($this, 'send_message'));
        add_action('wp_ajax_nopriv_vva_send_message', array($this, 'send_message'));

        add_action('wp_ajax_vva_start_conversation', array($this, 'start_conversation'));
        add_action('wp_ajax_nopriv_vva_start_conversation', array($this, 'start_conversation'));

        add_action('wp_ajax_vva_end_conversation', array($this, 'end_conversation'));
        add_action('wp_ajax_nopriv_vva_end_conversation', array($this, 'end_conversation'));

        add_action('wp_ajax_vva_get_history', array($this, 'get_history'));

        add_action('wp_ajax_vva_add_to_cart', array($this, 'add_to_cart'));
        add_action('wp_ajax_nopriv_vva_add_to_cart', array($this, 'add_to_cart'));

        // Admin AJAX actions
        add_action('wp_ajax_vva_test_api', array($this, 'test_api'));
        add_action('wp_ajax_vva_get_analytics', array($this, 'get_analytics'));
    }

    /**
     * Ensure WooCommerce cart and session are properly initialized
     * Based on WordPress.org WooCommerce documentation
     */
    private function ensure_cart_initialized() {
        // Ensure WooCommerce is loaded
        if (!function_exists('WC')) {
            error_log('VVA: WooCommerce not loaded');
            return false;
        }

        // Load cart if not already loaded
        if (WC()->cart === null) {
            error_log('VVA: Cart is null, initializing...');

            // Load WooCommerce frontend includes (cart, session, customer)
            if (!WC()->frontend_includes) {
                require_once WC_ABSPATH . 'includes/wc-cart-functions.php';
                require_once WC_ABSPATH . 'includes/wc-notice-functions.php';
            }

            // Initialize session
            if (WC()->session === null) {
                $session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
                WC()->session = new $session_class();
                WC()->session->init();
                error_log('VVA: Session initialized');
            }

            // Initialize cart
            WC()->cart = new WC_Cart();
            error_log('VVA: Cart object created');

            // Initialize customer
            if (WC()->customer === null) {
                WC()->customer = new WC_Customer(get_current_user_id(), true);
                error_log('VVA: Customer initialized');
            }
        }

        error_log('VVA: Cart initialization complete. Cart exists: ' . (WC()->cart ? 'YES' : 'NO'));
        return WC()->cart !== null;
    }

    /**
     * Verify nonce
     */
    private function verify_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vva-nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'valley-virtual-assistant')));
            exit;
        }
    }

    /**
     * Start a new conversation
     */
    public function start_conversation() {
        $this->verify_nonce();

        global $wpdb;
        $table = $wpdb->prefix . 'vva_conversations';

        // Generate session ID
        $session_id = $this->get_or_create_session_id();
        $user_id = get_current_user_id();
        $customer_email = $user_id ? wp_get_current_user()->user_email : null;

        // Insert conversation
        $wpdb->insert($table, array(
            'session_id' => $session_id,
            'user_id' => $user_id ? $user_id : null,
            'customer_email' => $customer_email,
            'started_at' => current_time('mysql'),
            'status' => 'active',
        ));

        $conversation_id = $wpdb->insert_id;

        // Store in session
        if (!session_id()) {
            session_start();
        }
        $_SESSION['vva_conversation_id'] = $conversation_id;

        wp_send_json_success(array(
            'conversation_id' => $conversation_id,
            'session_id' => $session_id,
            'welcome_message' => get_option('vva_welcome_message'),
        ));
    }

    /**
     * Send a message and get AI response
     */
    public function send_message() {
        $this->verify_nonce();

        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $conversation_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;

        if (empty($message)) {
            wp_send_json_error(array('message' => __('Message cannot be empty.', 'valley-virtual-assistant')));
        }

        if (!$conversation_id) {
            wp_send_json_error(array('message' => __('Invalid conversation ID.', 'valley-virtual-assistant')));
        }

        // Save user message
        $this->save_message($conversation_id, 'user', $message);

        // Get conversation history
        $history = $this->get_conversation_history($conversation_id);

        // Build context with product search
        $context = $this->build_context($message);

        // Get AI response
        $assistant = VVA_Assistant::instance();
        $response = $assistant->get_response($message, $history, $context);

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message(),
                'code' => $response->get_error_code(),
            ));
        }

        // Save assistant message
        $this->save_message($conversation_id, 'assistant', $response['content'], array(
            'model' => $response['model'],
            'usage' => $response['usage'],
        ));

        // Track analytics
        VVA_Analytics::track_event('message_sent', array(
            'conversation_id' => $conversation_id,
            'message_length' => strlen($message),
        ));

        // Parse product tags and get product data
        $products = $this->extract_products_from_message($response['content']);

        wp_send_json_success(array(
            'message' => $response['content'],
            'conversation_id' => $conversation_id,
            'products' => $products,
        ));
    }

    /**
     * End conversation
     */
    public function end_conversation() {
        $this->verify_nonce();

        $conversation_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;

        if (!$conversation_id) {
            wp_send_json_error(array('message' => __('Invalid conversation ID.', 'valley-virtual-assistant')));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vva_conversations';

        $wpdb->update(
            $table,
            array(
                'ended_at' => current_time('mysql'),
                'status' => 'ended',
            ),
            array('id' => $conversation_id)
        );

        wp_send_json_success(array('message' => __('Conversation ended.', 'valley-virtual-assistant')));
    }

    /**
     * Get conversation history
     */
    public function get_history() {
        $this->verify_nonce();

        $conversation_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;

        if (!$conversation_id) {
            wp_send_json_error(array('message' => __('Invalid conversation ID.', 'valley-virtual-assistant')));
        }

        $history = $this->get_conversation_history($conversation_id);

        wp_send_json_success(array('history' => $history));
    }

    /**
     * Add product to cart (WooCommerce compatible)
     * Based on WordPress.org WooCommerce AJAX add to cart documentation
     */
    public function add_to_cart() {
        error_log('VVA: add_to_cart AJAX handler called');

        $this->verify_nonce();

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
        $variation = isset($_POST['variation']) ? (array) $_POST['variation'] : array();

        error_log('VVA: Attempting to add product ' . $product_id . ' to cart, quantity: ' . $quantity);

        if (!$product_id) {
            error_log('VVA: Invalid product ID');
            wp_send_json_error(array('message' => __('Invalid product ID.', 'valley-virtual-assistant')));
        }

        // Verify WooCommerce is active
        if (!function_exists('WC')) {
            error_log('VVA: WooCommerce not active');
            wp_send_json_error(array('message' => __('WooCommerce is not active.', 'valley-virtual-assistant')));
        }

        // Ensure cart and session are initialized (critical for AJAX)
        if (!$this->ensure_cart_initialized()) {
            error_log('VVA: Failed to initialize cart');
            wp_send_json_error(array('message' => __('Cart could not be initialized. Please refresh the page and try again.', 'valley-virtual-assistant')));
        }

        // Get product object
        $product = wc_get_product($product_id);
        if (!$product) {
            error_log('VVA: Product not found: ' . $product_id);
            wp_send_json_error(array('message' => __('Product not found.', 'valley-virtual-assistant')));
        }

        error_log('VVA: Product found: ' . $product->get_name() . ' (ID: ' . $product->get_id() . ')');

        // Check if product is in stock
        if (!$product->is_in_stock()) {
            error_log('VVA: Product out of stock: ' . $product_id);
            wp_send_json_error(array('message' => __('Sorry, this product is currently out of stock.', 'valley-virtual-assistant')));
        }

        // Check if product is purchasable
        if (!$product->is_purchasable()) {
            error_log('VVA: Product not purchasable: ' . $product_id);
            wp_send_json_error(array('message' => __('This product cannot be purchased.', 'valley-virtual-assistant')));
        }

        // Add to cart using WooCommerce method
        error_log('VVA: Calling WC()->cart->add_to_cart()');
        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation);

        if ($cart_item_key) {
            error_log('VVA: Product successfully added to cart. Cart item key: ' . $cart_item_key);

            // Track analytics
            VVA_Analytics::track_event('product_added_to_cart', array(
                'product_id' => $product_id,
                'quantity' => $quantity,
                'source' => 'virtual_assistant',
            ));

            // Trigger WooCommerce added to cart action (for themes/plugins)
            do_action('woocommerce_ajax_added_to_cart', $product_id);

            // Get cart counts
            $cart_count = WC()->cart->get_cart_contents_count();
            $cart_total = WC()->cart->get_cart_total();

            error_log('VVA: Cart count after add: ' . $cart_count);

            // Get WooCommerce cart fragments for frontend updates
            $fragments = array();
            if (function_exists('wc_cart_fragments')) {
                ob_start();
                woocommerce_mini_cart();
                $mini_cart = ob_get_clean();

                $fragments['div.widget_shopping_cart_content'] = '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>';
                $fragments['.cart-contents-count'] = $cart_count;
                $fragments['.cart-count'] = $cart_count;

                // Apply WooCommerce fragments filter
                $fragments = apply_filters('woocommerce_add_to_cart_fragments', $fragments);

                error_log('VVA: Cart fragments generated: ' . count($fragments) . ' fragments');
            }

            wp_send_json_success(array(
                'message' => sprintf(__('%s has been added to your cart!', 'valley-virtual-assistant'), $product->get_name()),
                'cart_count' => $cart_count,
                'cart_total' => $cart_total,
                'cart_url' => wc_get_cart_url(),
                'product_name' => $product->get_name(),
                'fragments' => $fragments, // Add fragments for theme cart updates
            ));
        } else {
            error_log('VVA: Failed to add product to cart. Cart returned false/null');

            // Check for WooCommerce notices/errors
            $notices = wc_get_notices('error');
            $error_message = __('Failed to add product to cart. Please try again.', 'valley-virtual-assistant');

            if (!empty($notices)) {
                error_log('VVA: WooCommerce errors: ' . print_r($notices, true));
                $error_message = reset($notices);
                if (is_array($error_message) && isset($error_message['notice'])) {
                    $error_message = $error_message['notice'];
                }
            }

            wp_send_json_error(array('message' => $error_message));
        }
    }

    /**
     * Test API connection (admin only)
     */
    public function test_api() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'valley-virtual-assistant')));
        }

        check_ajax_referer('vva-admin-nonce', 'nonce');

        $assistant = VVA_Assistant::instance();
        $result = $assistant->test_connection();

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'code' => $result->get_error_code(),
            ));
        }

        wp_send_json_success($result);
    }

    /**
     * Get analytics data (admin only)
     */
    public function get_analytics() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'valley-virtual-assistant')));
        }

        check_ajax_referer('vva-admin-nonce', 'nonce');

        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '7days';

        global $wpdb;

        // Get conversation stats
        $conversations_table = $wpdb->prefix . 'vva_conversations';
        $messages_table = $wpdb->prefix . 'vva_messages';

        $total_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table");
        $total_messages = $wpdb->get_var("SELECT COUNT(*) FROM $messages_table");
        $avg_messages_per_conversation = $total_conversations > 0 ? $total_messages / $total_conversations : 0;

        wp_send_json_success(array(
            'total_conversations' => $total_conversations,
            'total_messages' => $total_messages,
            'avg_messages_per_conversation' => round($avg_messages_per_conversation, 2),
        ));
    }

    /**
     * Save message to database
     */
    private function save_message($conversation_id, $role, $content, $metadata = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'vva_messages';

        $wpdb->insert($table, array(
            'conversation_id' => $conversation_id,
            'role' => $role,
            'content' => $content,
            'metadata' => !empty($metadata) ? json_encode($metadata) : null,
            'created_at' => current_time('mysql'),
        ));

        return $wpdb->insert_id;
    }

    /**
     * Get conversation history
     */
    private function get_conversation_history($conversation_id, $limit = 20) {
        global $wpdb;
        $table = $wpdb->prefix . 'vva_messages';

        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT role, content, created_at FROM $table WHERE conversation_id = %d ORDER BY id DESC LIMIT %d",
            $conversation_id,
            $limit
        ), ARRAY_A);

        // Reverse to chronological order
        $messages = array_reverse($messages);

        // Format for Claude API
        $formatted = array();
        foreach ($messages as $message) {
            $formatted[] = array(
                'role' => $message['role'],
                'content' => $message['content'],
            );
        }

        return $formatted;
    }

    /**
     * Build context for AI
     */
    private function build_context($user_message = '') {
        $context = array();

        // Add current product if on product page
        if (is_product()) {
            global $product;
            if ($product) {
                $wc = VVA_WooCommerce::instance();
                $context['current_product'] = $wc->get_product_info($product->get_id());
            }
        }

        // Search for relevant products based on user message
        if (!empty($user_message)) {
            $wc = VVA_WooCommerce::instance();

            // Perform a broad search with the user's message
            // NOTE: Don't filter by stock status - let AI see ALL products and their stock status
            // This way AI can inform user about out-of-stock items instead of saying nothing is available
            $search_results = $wc->search_products(array(
                's' => $user_message,
                'limit' => 10,
                'in_stock' => false, // Changed to false - get ALL products regardless of stock
            ));

            if (!empty($search_results['products'])) {
                $context['relevant_products'] = $search_results['products'];

                // Count how many are in stock
                $in_stock_count = 0;
                foreach ($search_results['products'] as $prod) {
                    if ($prod['in_stock']) {
                        $in_stock_count++;
                    }
                }

                error_log('VVA: Found ' . count($search_results['products']) . ' relevant products for: ' . $user_message . ' (' . $in_stock_count . ' in stock)');
            } else {
                error_log('VVA: No products found for search: ' . $user_message);
            }
        }

        // Add user info if logged in
        $user_id = get_current_user_id();
        if ($user_id) {
            $context['customer_id'] = $user_id;

            // Add recent orders
            $wc = VVA_WooCommerce::instance();
            $context['customer_orders'] = $wc->get_customer_orders($user_id, array('limit' => 5));
        }

        // Add browsing history
        $wc = VVA_WooCommerce::instance();
        $context['browsing_history'] = $wc->get_recently_viewed();

        // Add cart items
        if (WC()->cart) {
            $context['cart_items'] = $wc->get_cart_info();
        }

        return $context;
    }

    /**
     * Get or create session ID
     */
    private function get_or_create_session_id() {
        if (isset($_COOKIE['vva_session_id'])) {
            return sanitize_text_field($_COOKIE['vva_session_id']);
        }

        $session_id = wp_generate_password(32, false);
        setcookie('vva_session_id', $session_id, time() + (86400 * 30), '/');

        return $session_id;
    }

    /**
     * Extract products from message with [PRODUCT:id] tags
     *
     * @param string $message Message content
     * @return array Product data
     */
    private function extract_products_from_message($message) {
        $products = array();

        // Match [PRODUCT:123] pattern
        if (preg_match_all('/\[PRODUCT:(\d+)\]/', $message, $matches)) {
            $product_ids = $matches[1];
            $wc = VVA_WooCommerce::instance();

            error_log('VVA: Extracting products from message, found IDs: ' . implode(', ', $product_ids));

            foreach ($product_ids as $product_id) {
                $product_data = $wc->get_product_info((int) $product_id);

                if (!is_wp_error($product_data)) {
                    error_log('VVA: Product ' . $product_id . ' image URL: ' . ($product_data['image'] ?? 'NULL'));

                    $products[] = array(
                        'id' => $product_data['id'],
                        'name' => $product_data['name'],
                        'price' => $product_data['price'],
                        'regular_price' => $product_data['regular_price'],
                        'sale_price' => $product_data['sale_price'],
                        'on_sale' => $product_data['on_sale'],
                        'stock_status' => $product_data['stock_status'],
                        'in_stock' => $product_data['in_stock'],
                        'url' => $product_data['url'],
                        'image' => $product_data['image'],
                        'short_description' => $product_data['short_description'],
                    );
                } else {
                    error_log('VVA: Failed to get product data for ID ' . $product_id . ': ' . $product_data->get_error_message());
                }
            }

            error_log('VVA: Returning ' . count($products) . ' products to frontend');

            // Log detailed product data for debugging
            foreach ($products as $idx => $product) {
                error_log('VVA: Product #' . ($idx + 1) . ' - ID: ' . $product['id'] . ', Name: ' . $product['name'] . ', Image: ' . $product['image'] . ', Has description: ' . (!empty($product['short_description']) ? 'YES' : 'NO'));
            }
        }

        return $products;
    }
}

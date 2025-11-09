<?php
/**
 * Valley Virtual Assistant - WooCommerce Integration
 *
 * @package Valley_Virtual_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VVA_WooCommerce Class
 */
class VVA_WooCommerce {

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
        // Only initialize if WooCommerce is active
        if (class_exists('WooCommerce')) {
            $this->init_hooks();
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add assistant context to product pages
        add_action('woocommerce_after_single_product_summary', array($this, 'add_product_assistant'), 15);

        // Track product views
        add_action('woocommerce_after_single_product', array($this, 'track_product_view'));

        // Track cart additions
        add_action('woocommerce_add_to_cart', array($this, 'track_cart_addition'), 10, 6);
    }

    /**
     * Get order details securely
     *
     * @param int $order_id Order ID
     * @param int $user_id User ID for verification
     * @return array|WP_Error Order details or error
     */
    public function get_order_details($order_id, $user_id = null) {
        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error('invalid_order', __('Order not found.', 'valley-virtual-assistant'));
        }

        // Verify user has permission to view this order
        if ($user_id && $order->get_user_id() !== $user_id) {
            return new WP_Error('unauthorized', __('You do not have permission to view this order.', 'valley-virtual-assistant'));
        }

        // Build order details (sanitized for assistant)
        $order_data = array(
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'status_name' => wc_get_order_status_name($order->get_status()),
            'date_created' => $order->get_date_created()->date('Y-m-d H:i:s'),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
            'payment_method' => $order->get_payment_method_title(),
            'items' => array(),
            'shipping' => array(
                'method' => $order->get_shipping_method(),
                'total' => $order->get_shipping_total(),
            ),
            'tracking' => $this->get_tracking_info($order),
        );

        // Get order items (discreet product names)
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $order_data['items'][] = array(
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total' => $item->get_total(),
                'product_id' => $item->get_product_id(),
            );
        }

        return $order_data;
    }

    /**
     * Get customer order history
     *
     * @param int $customer_id Customer/User ID
     * @param array $args Query arguments
     * @return array Order history
     */
    public function get_customer_orders($customer_id, $args = array()) {
        $defaults = array(
            'limit' => 10,
            'status' => array('wc-completed', 'wc-processing', 'wc-on-hold'),
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);
        $args['customer_id'] = $customer_id;

        $orders = wc_get_orders($args);
        $order_history = array();

        foreach ($orders as $order) {
            $order_history[] = array(
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'status' => $order->get_status(),
                'status_name' => wc_get_order_status_name($order->get_status()),
                'date' => $order->get_date_created()->date('Y-m-d'),
                'total' => $order->get_total(),
                'currency' => $order->get_currency(),
                'item_count' => $order->get_item_count(),
            );
        }

        return $order_history;
    }

    /**
     * Get product information for assistant
     *
     * @param int $product_id Product ID
     * @return array|WP_Error Product data or error
     */
    public function get_product_info($product_id) {
        $product = wc_get_product($product_id);

        if (!$product) {
            return new WP_Error('invalid_product', __('Product not found.', 'valley-virtual-assistant'));
        }

        $product_data = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'type' => $product->get_type(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'on_sale' => $product->is_on_sale(),
            'stock_status' => $product->get_stock_status(),
            'in_stock' => $product->is_in_stock(),
            'categories' => $this->get_product_categories($product),
            'tags' => $this->get_product_tags($product),
            'attributes' => $this->get_product_attributes($product),
            'rating' => $product->get_average_rating(),
            'review_count' => $product->get_review_count(),
            'url' => $product->get_permalink(),
            'image' => wp_get_attachment_url($product->get_image_id()),
        );

        // Add variation data for variable products
        if ($product->is_type('variable')) {
            $product_data['variations'] = $this->get_product_variations($product);
        }

        return $product_data;
    }

    /**
     * Search products for assistant
     *
     * @param array $search_args Search parameters
     * @return array Products matching search
     */
    public function search_products($search_args) {
        $defaults = array(
            's' => '',
            'category' => '',
            'tag' => '',
            'limit' => 10,
            'orderby' => 'relevance',
            'on_sale' => null,
            'in_stock' => true,
            'min_price' => null,
            'max_price' => null,
        );

        $args = wp_parse_args($search_args, $defaults);

        // Build WooCommerce query
        $query_args = array(
            'post_type' => 'product',
            'posts_per_page' => $args['limit'],
            'post_status' => 'publish',
        );

        // Search query
        if (!empty($args['s'])) {
            $query_args['s'] = sanitize_text_field($args['s']);
        }

        // Category filter
        if (!empty($args['category'])) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => sanitize_text_field($args['category']),
            );
        }

        // Tag filter
        if (!empty($args['tag'])) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'product_tag',
                'field' => 'slug',
                'terms' => sanitize_text_field($args['tag']),
            );
        }

        // Price range
        if (!is_null($args['min_price']) || !is_null($args['max_price'])) {
            $query_args['meta_query'][] = array(
                'key' => '_price',
                'value' => array($args['min_price'], $args['max_price']),
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC',
            );
        }

        // Stock status
        if ($args['in_stock']) {
            $query_args['meta_query'][] = array(
                'key' => '_stock_status',
                'value' => 'instock',
            );
        }

        $products_query = new WP_Query($query_args);
        $products = array();

        if ($products_query->have_posts()) {
            while ($products_query->have_posts()) {
                $products_query->the_post();
                $product_id = get_the_ID();
                $product = wc_get_product($product_id);

                if ($product) {
                    $products[] = array(
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'price' => $product->get_price(),
                        'sale_price' => $product->get_sale_price(),
                        'on_sale' => $product->is_on_sale(),
                        'stock_status' => $product->get_stock_status(),
                        'short_description' => wp_trim_words($product->get_short_description(), 20),
                        'categories' => $this->get_product_categories($product),
                        'url' => $product->get_permalink(),
                        'image' => wp_get_attachment_url($product->get_image_id()),
                    );
                }
            }
            wp_reset_postdata();
        }

        return array(
            'products' => $products,
            'total' => $products_query->found_posts,
            'query' => $args,
        );
    }

    /**
     * Get product categories
     */
    private function get_product_categories($product) {
        $categories = array();
        $terms = get_the_terms($product->get_id(), 'product_cat');

        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $categories[] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                );
            }
        }

        return $categories;
    }

    /**
     * Get product tags
     */
    private function get_product_tags($product) {
        $tags = array();
        $terms = get_the_terms($product->get_id(), 'product_tag');

        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $tags[] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                );
            }
        }

        return $tags;
    }

    /**
     * Get product attributes
     */
    private function get_product_attributes($product) {
        $attributes = array();
        $product_attributes = $product->get_attributes();

        foreach ($product_attributes as $attribute) {
            $attributes[] = array(
                'name' => wc_attribute_label($attribute->get_name()),
                'options' => $attribute->get_options(),
                'visible' => $attribute->get_visible(),
            );
        }

        return $attributes;
    }

    /**
     * Get product variations
     */
    private function get_product_variations($product) {
        $variations = array();
        $variation_ids = $product->get_children();

        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product($variation_id);
            if ($variation) {
                $variations[] = array(
                    'id' => $variation->get_id(),
                    'attributes' => $variation->get_variation_attributes(),
                    'price' => $variation->get_price(),
                    'stock_status' => $variation->get_stock_status(),
                );
            }
        }

        return $variations;
    }

    /**
     * Get tracking information
     */
    private function get_tracking_info($order) {
        // Check for common tracking plugins
        $tracking = array();

        // WooCommerce Shipment Tracking
        if (function_exists('wc_st_add_tracking_number')) {
            $tracking_items = get_post_meta($order->get_id(), '_wc_shipment_tracking_items', true);
            if (!empty($tracking_items)) {
                foreach ($tracking_items as $item) {
                    $tracking[] = array(
                        'provider' => $item['tracking_provider'],
                        'number' => $item['tracking_number'],
                        'date' => isset($item['date_shipped']) ? $item['date_shipped'] : '',
                    );
                }
            }
        }

        return $tracking;
    }

    /**
     * Get current cart information
     */
    public function get_cart_info() {
        if (!WC()->cart) {
            return array();
        }

        $cart_items = array();
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $cart_items[] = array(
                'product_id' => $cart_item['product_id'],
                'name' => $product->get_name(),
                'quantity' => $cart_item['quantity'],
                'price' => $product->get_price(),
                'total' => $cart_item['line_total'],
            );
        }

        return array(
            'items' => $cart_items,
            'total' => WC()->cart->get_total(''),
            'item_count' => WC()->cart->get_cart_contents_count(),
        );
    }

    /**
     * Track product view
     */
    public function track_product_view() {
        // Track in user session for context
        if (!WC()->session) {
            return;
        }

        $product_id = get_the_ID();
        $viewed_products = WC()->session->get('vva_viewed_products', array());

        // Add to beginning of array
        array_unshift($viewed_products, $product_id);

        // Keep only last 10 viewed products
        $viewed_products = array_slice(array_unique($viewed_products), 0, 10);

        WC()->session->set('vva_viewed_products', $viewed_products);
    }

    /**
     * Get recently viewed products
     */
    public function get_recently_viewed() {
        if (!WC()->session) {
            return array();
        }

        $viewed_ids = WC()->session->get('vva_viewed_products', array());
        $products = array();

        foreach ($viewed_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $products[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price(),
                    'url' => $product->get_permalink(),
                );
            }
        }

        return $products;
    }

    /**
     * Track cart addition
     */
    public function track_cart_addition($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        // Log analytics event
        VVA_Analytics::track_event('cart_addition', array(
            'product_id' => $product_id,
            'quantity' => $quantity,
            'variation_id' => $variation_id,
        ));
    }

    /**
     * Add product assistant to product page
     */
    public function add_product_assistant() {
        if (!get_option('vva_widget_enabled')) {
            return;
        }

        $product_id = get_the_ID();
        echo '<div class="vva-product-assistant" data-product-id="' . esc_attr($product_id) . '"></div>';
    }
}

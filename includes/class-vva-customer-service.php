<?php
/**
 * Valley Virtual Assistant - Customer Service
 *
 * @package Valley_Virtual_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VVA_Customer_Service Class
 */
class VVA_Customer_Service {

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
        // Initialize
    }

    /**
     * Get customer orders
     *
     * @param array $params Query parameters
     * @return array|WP_Error Orders or error
     */
    public function get_customer_orders($params) {
        $customer_id = get_current_user_id();

        if (!$customer_id) {
            return new WP_Error('not_logged_in', __('Please log in to view your orders.', 'valley-virtual-assistant'));
        }

        $order_id = isset($params['order_id']) ? absint($params['order_id']) : null;
        $status = isset($params['status']) ? sanitize_text_field($params['status']) : '';

        $wc = VVA_WooCommerce::instance();

        // Get specific order
        if ($order_id) {
            $order = $wc->get_order_details($order_id, $customer_id);
            return is_wp_error($order) ? $order : array('order' => $order);
        }

        // Get order history
        $args = array('limit' => 10);
        if (!empty($status)) {
            $args['status'] = array('wc-' . $status);
        }

        $orders = $wc->get_customer_orders($customer_id, $args);

        return array(
            'orders' => $orders,
            'total' => count($orders),
        );
    }

    /**
     * Get customer information (secure)
     *
     * @param int $customer_id Customer ID
     * @return array|WP_Error Customer data
     */
    public function get_customer_info($customer_id = null) {
        if (!$customer_id) {
            $customer_id = get_current_user_id();
        }

        if (!$customer_id) {
            return new WP_Error('invalid_customer', __('Invalid customer ID.', 'valley-virtual-assistant'));
        }

        // Verify permission
        if ($customer_id !== get_current_user_id() && !current_user_can('manage_woocommerce')) {
            return new WP_Error('unauthorized', __('You do not have permission to view this information.', 'valley-virtual-assistant'));
        }

        $customer = new WC_Customer($customer_id);

        return array(
            'id' => $customer->get_id(),
            'email' => $customer->get_email(),
            'first_name' => $customer->get_first_name(),
            'last_name' => $customer->get_last_name(),
            'username' => $customer->get_username(),
            'billing' => array(
                'city' => $customer->get_billing_city(),
                'state' => $customer->get_billing_state(),
                'postcode' => $customer->get_billing_postcode(),
                'country' => $customer->get_billing_country(),
            ),
            'orders_count' => $customer->get_order_count(),
            'total_spent' => $customer->get_total_spent(),
        );
    }

    /**
     * Get FAQ content
     *
     * @return array FAQ items
     */
    public function get_faq() {
        $faq = array(
            array(
                'question' => 'How discreet is your shipping?',
                'answer' => 'All orders are shipped in plain, unmarked packaging with a discreet sender name. There is nothing on the outside of the package that would indicate the contents. Your privacy is our top priority.',
                'category' => 'shipping',
            ),
            array(
                'question' => 'What is your return policy?',
                'answer' => 'We accept returns of unopened items within 30 days of purchase. For hygiene reasons, we cannot accept returns of opened intimate products. Please contact us if you have any concerns about your order.',
                'category' => 'returns',
            ),
            array(
                'question' => 'How long does shipping take?',
                'answer' => 'Standard shipping within Australia typically takes 3-7 business days. Express shipping is available and usually arrives within 1-3 business days. You will receive a tracking number once your order ships.',
                'category' => 'shipping',
            ),
            array(
                'question' => 'Is my payment information secure?',
                'answer' => 'Yes, absolutely. We use industry-standard encryption and never store your complete payment information. All transactions are processed securely through trusted payment gateways.',
                'category' => 'payment',
            ),
            array(
                'question' => 'How will the charge appear on my statement?',
                'answer' => 'Charges appear with a discreet descriptor that does not indicate the nature of the purchase. You can contact us for the exact descriptor name.',
                'category' => 'payment',
            ),
            array(
                'question' => 'Do you have a physical store?',
                'answer' => 'We are primarily an online retailer, which allows us to offer a wider selection and more discreet shopping experience. However, please contact us if you have specific questions about our operations.',
                'category' => 'general',
            ),
        );

        return apply_filters('vva_faq_items', $faq);
    }

    /**
     * Search FAQ
     *
     * @param string $query Search query
     * @return array Matching FAQ items
     */
    public function search_faq($query) {
        $all_faq = $this->get_faq();
        $results = array();

        $query = strtolower($query);

        foreach ($all_faq as $item) {
            $question = strtolower($item['question']);
            $answer = strtolower($item['answer']);

            if (strpos($question, $query) !== false || strpos($answer, $query) !== false) {
                $results[] = $item;
            }
        }

        return $results;
    }

    /**
     * Get shipping information
     *
     * @return array Shipping details
     */
    public function get_shipping_info() {
        return array(
            'methods' => array(
                array(
                    'name' => 'Standard Shipping',
                    'duration' => '3-7 business days',
                    'cost' => 'Calculated at checkout',
                ),
                array(
                    'name' => 'Express Shipping',
                    'duration' => '1-3 business days',
                    'cost' => 'Calculated at checkout',
                ),
            ),
            'discretion' => 'All packages are shipped in plain, unmarked packaging',
            'tracking' => 'Tracking number provided for all orders',
            'international' => 'International shipping available to select countries',
        );
    }

    /**
     * Get privacy policy summary
     *
     * @return array Privacy details
     */
    public function get_privacy_summary() {
        return array(
            'data_collection' => 'We collect only essential information needed to process your order and improve our service.',
            'data_usage' => 'Your information is used solely for order processing, customer service, and improving our virtual assistant.',
            'data_sharing' => 'We never share your personal information with third parties for marketing purposes.',
            'data_security' => 'All data is encrypted and stored securely. We follow industry best practices for data protection.',
            'data_retention' => 'Chat conversations are retained for ' . get_option('vva_data_retention_days', 90) . ' days.',
            'your_rights' => 'You can request access to, correction of, or deletion of your data at any time.',
        );
    }

    /**
     * Check order status
     *
     * @param string $order_number Order number
     * @param string $email Customer email
     * @return array|WP_Error Order status
     */
    public function check_order_status($order_number, $email) {
        // Sanitize inputs
        $order_number = sanitize_text_field($order_number);
        $email = sanitize_email($email);

        if (empty($order_number) || empty($email)) {
            return new WP_Error('missing_info', __('Please provide both order number and email address.', 'valley-virtual-assistant'));
        }

        // Find order
        $orders = wc_get_orders(array(
            'order_number' => $order_number,
            'billing_email' => $email,
            'limit' => 1,
        ));

        if (empty($orders)) {
            return new WP_Error('order_not_found', __('No order found with that order number and email address.', 'valley-virtual-assistant'));
        }

        $order = reset($orders);
        $wc = VVA_WooCommerce::instance();

        return $wc->get_order_details($order->get_id());
    }
}

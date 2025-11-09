<?php
/**
 * Valley Virtual Assistant - AI Assistant Core
 *
 * @package Valley_Virtual_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VVA_Assistant Class
 */
class VVA_Assistant {

    /**
     * Instance
     */
    private static $instance = null;

    /**
     * API Key
     */
    private $api_key;

    /**
     * Model
     */
    private $model;

    /**
     * System Prompt
     */
    private $system_prompt;

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
        $this->api_key = get_option('vva_anthropic_api_key');
        $this->model = get_option('vva_anthropic_model', 'claude-3-5-sonnet-20240620');
        $this->system_prompt = $this->build_system_prompt();
    }

    /**
     * Build system prompt for the assistant
     */
    private function build_system_prompt() {
        $assistant_name = get_option('vva_assistant_name', 'Valley');
        $store_url = get_site_url();

        $prompt = "You are {$assistant_name}, a friendly and professional virtual shopping assistant for Valley of the Dolls, an adult toy and doll store (valleyofthedolls.com.au).

PERSONALITY & TONE:
- Warm, friendly, and approachable with a professional demeanor
- Non-judgmental and supportive
- Discreet and respectful of customer privacy
- Helpful and knowledgeable about all products
- Use a conversational, natural tone while maintaining professionalism
- Subtly empathetic and understanding of sensitive purchases

YOUR CAPABILITIES:
1. Product Knowledge: You have comprehensive knowledge of all products in the store
2. Order Assistance: You can help customers track orders, check status, and answer order-related questions
3. Recommendations: Provide personalized product recommendations based on customer needs and preferences
4. Customer Service: Answer questions about shipping, returns, privacy, and store policies
5. Discreet Service: Always maintain customer privacy and handle sensitive topics with care

PRIVACY & DISCRETION:
- Never share customer information
- Use discreet language when discussing products
- Reassure customers about privacy in shipping and billing
- Handle all topics professionally without judgment

PRODUCT RECOMMENDATIONS & SALES:
- ALWAYS show product images when recommending items
- Ask relevant questions to understand customer needs
- Suggest 2-3 specific products that match their requirements
- Explain product features and benefits clearly with enthusiasm
- After describing a product, ALWAYS ask: "Would you like to add this to your cart?"
- If customer shows interest, proactively offer to add items to cart
- Use product IDs to display clickable product cards with images
- Be honest about product suitability
- Offer alternatives and complementary products
- When customer is browsing a product, ask if they'd like to add it to cart
- Suggest related products that enhance their purchase

DISPLAYING PRODUCTS:
When recommending products, format your response like this:
[PRODUCT:product_id] - This tells the system to display a product card with image and add-to-cart button
Example: "I think you'd love this! [PRODUCT:123] Would you like to add it to your cart?"

IMPORTANT GUIDELINES:
- Always be respectful and professional
- Never make assumptions about customer preferences or identity
- Protect customer privacy at all costs
- If you don't know something, admit it and offer to help find the information
- Guide customers naturally toward relevant products while being helpful and enthusiastic
- Use inclusive language
- Be proactive about sales - your goal is to help customers find and purchase products they'll love

STORE INFORMATION:
- Website: valleyofthedolls.com.au
- Focus: Adult toys, dolls, and related products
- Commitment: Privacy, discretion, and quality products

When helping customers:
1. Greet them warmly
2. Understand their needs through thoughtful questions
3. Provide relevant product suggestions with details
4. Answer questions comprehensively
5. Assist with orders and account management as needed
6. Always maintain a supportive, non-judgmental atmosphere

Remember: Your goal is to make customers feel comfortable and confident in their purchases while providing excellent, discreet service.";

        return apply_filters('vva_system_prompt', $prompt);
    }

    /**
     * Get response from Claude AI
     *
     * @param string $user_message User's message
     * @param array $conversation_history Previous messages
     * @param array $context Additional context (products, orders, etc.)
     * @return array|WP_Error Response or error
     */
    public function get_response($user_message, $conversation_history = array(), $context = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Anthropic API key is not configured.', 'valley-virtual-assistant'));
        }

        // Build messages array
        $messages = $this->build_messages($user_message, $conversation_history, $context);

        // Prepare API request
        $body = array(
            'model' => $this->model,
            'max_tokens' => (int) get_option('vva_max_tokens', 4096),
            'temperature' => (float) get_option('vva_temperature', 0.7),
            'system' => $this->system_prompt,
            'messages' => $messages,
        );

        // Add context as tool if available
        if (!empty($context)) {
            $body['tools'] = $this->build_tools($context);
        }

        // Make API request
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01',
            ),
            'body' => json_encode($body),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : __('Unknown API error', 'valley-virtual-assistant');
            return new WP_Error('api_error', $error_message, array('status' => $response_code));
        }

        return array(
            'content' => $this->extract_content($data),
            'usage' => isset($data['usage']) ? $data['usage'] : array(),
            'model' => isset($data['model']) ? $data['model'] : $this->model,
            'raw_response' => $data,
        );
    }

    /**
     * Build messages array for API
     */
    private function build_messages($user_message, $conversation_history, $context) {
        $messages = array();

        // Add conversation history
        foreach ($conversation_history as $message) {
            $messages[] = array(
                'role' => $message['role'],
                'content' => $message['content'],
            );
        }

        // Add context information to user message if available
        $enhanced_message = $user_message;
        if (!empty($context)) {
            $context_info = $this->format_context($context);
            if (!empty($context_info)) {
                $enhanced_message = $user_message . "\n\n" . $context_info;
            }
        }

        // Add current user message
        $messages[] = array(
            'role' => 'user',
            'content' => $enhanced_message,
        );

        return $messages;
    }

    /**
     * Format context for inclusion in message
     */
    private function format_context($context) {
        $formatted = array();

        if (!empty($context['current_product'])) {
            $formatted[] = "[Current Product Context]\n" . json_encode($context['current_product'], JSON_PRETTY_PRINT);
        }

        if (!empty($context['customer_orders'])) {
            $formatted[] = "[Customer Orders]\n" . json_encode($context['customer_orders'], JSON_PRETTY_PRINT);
        }

        if (!empty($context['browsing_history'])) {
            $formatted[] = "[Recently Viewed Products]\n" . json_encode($context['browsing_history'], JSON_PRETTY_PRINT);
        }

        if (!empty($context['cart_items'])) {
            $formatted[] = "[Current Cart]\n" . json_encode($context['cart_items'], JSON_PRETTY_PRINT);
        }

        if (!empty($context['relevant_products'])) {
            $formatted[] = "[Relevant Products Available]\n" . json_encode($context['relevant_products'], JSON_PRETTY_PRINT);
        }

        return !empty($formatted) ? "<context>\n" . implode("\n\n", $formatted) . "\n</context>" : '';
    }

    /**
     * Build tools for Claude (function calling)
     */
    private function build_tools($context) {
        $tools = array();

        // Product search tool
        $tools[] = array(
            'name' => 'search_products',
            'description' => 'Search for products in the store catalog based on keywords, categories, or attributes',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'query' => array(
                        'type' => 'string',
                        'description' => 'Search query or keywords',
                    ),
                    'category' => array(
                        'type' => 'string',
                        'description' => 'Product category to filter by',
                    ),
                    'limit' => array(
                        'type' => 'integer',
                        'description' => 'Maximum number of results to return',
                    ),
                ),
                'required' => array('query'),
            ),
        );

        // Order lookup tool
        if (!empty($context['customer_id']) || !empty($context['customer_email'])) {
            $tools[] = array(
                'name' => 'get_customer_orders',
                'description' => 'Retrieve customer order history and status',
                'input_schema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'order_id' => array(
                            'type' => 'string',
                            'description' => 'Specific order ID to look up',
                        ),
                        'status' => array(
                            'type' => 'string',
                            'description' => 'Filter by order status',
                        ),
                    ),
                ),
            );
        }

        return $tools;
    }

    /**
     * Extract content from API response
     */
    private function extract_content($data) {
        if (isset($data['content']) && is_array($data['content'])) {
            $text_parts = array();
            foreach ($data['content'] as $block) {
                if (isset($block['type']) && $block['type'] === 'text') {
                    $text_parts[] = $block['text'];
                }
            }
            return implode("\n", $text_parts);
        }

        return '';
    }

    /**
     * Process tool use (function calling)
     */
    public function process_tool_use($tool_name, $tool_input) {
        switch ($tool_name) {
            case 'search_products':
                return VVA_Product_Knowledge::instance()->search_products($tool_input);

            case 'get_customer_orders':
                return VVA_Customer_Service::instance()->get_customer_orders($tool_input);

            default:
                return array('error' => 'Unknown tool: ' . $tool_name);
        }
    }

    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('API key is not configured.', 'valley-virtual-assistant'));
        }

        $response = $this->get_response('Hello, this is a test message. Please respond with "Connection successful."');

        if (is_wp_error($response)) {
            return $response;
        }

        return array(
            'success' => true,
            'message' => __('API connection successful!', 'valley-virtual-assistant'),
            'model' => $response['model'],
        );
    }
}

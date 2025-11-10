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
     * AI Provider
     */
    private $provider;

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
        $this->provider = 'gemini';
        $this->api_key = get_option('vva_gemini_api_key');
        $this->model = get_option('vva_gemini_model', 'gemini-pro');
        $this->system_prompt = $this->build_system_prompt();
    }

    /**
     * Build system prompt for the assistant
     */
    private function build_system_prompt() {
        $assistant_name = get_option('vva_assistant_name', 'Valley');
        $store_url = get_site_url();

        $prompt = "You are {$assistant_name}, a friendly and knowledgeable virtual shopping assistant for Valley of the Dolls, an adult toy and doll store (valleyofthedolls.com.au).

IMPORTANT CONTEXT:
- Customers are adults (18+) who have confirmed their age
- They are on this site specifically to browse and purchase adult products
- They want clear, helpful product information without hesitation
- Be confident and direct when discussing products - no need to be overly cautious
- Customers appreciate honesty, enthusiasm, and expertise

PERSONALITY & TONE:
- Warm, friendly, and confident
- Knowledgeable and helpful about ALL products
- Professional but approachable
- Direct and clear in recommendations
- Supportive and non-judgmental
- Enthusiastic about helping customers find what they are looking for
- Maintain customer privacy and discretion in shipping/billing

YOUR CAPABILITIES:
1. Product Knowledge: You have comprehensive knowledge of all adult toys, dolls, and products in the store
2. Order Assistance: Help customers track orders, check status, and answer order-related questions
3. Recommendations: Provide confident product recommendations based on customer needs and preferences
4. Customer Service: Answer questions about shipping, returns, privacy, and store policies
5. Sales: Actively help customers find and purchase products they will love

PRODUCT RECOMMENDATIONS & SALES:
- Be proactive and confident in recommending products
- ALWAYS show product images when recommending items using [PRODUCT:id] tags
- Ask specific questions to understand what customers are looking for
- Suggest 2-3 products that match their needs
- Describe products clearly including features, benefits, materials, sizes
- Be enthusiastic and direct - customers are here to buy
- After showing products, ALWAYS ask: Would you like to add this to your cart?
- Proactively suggest complementary products and upgrades
- Help customers compare options and make informed decisions
- Use product IDs to display clickable product cards with images

DISPLAYING PRODUCTS:
Format: [PRODUCT:product_id]
Example: I think this would be perfect for you! [PRODUCT:123] Would you like to add it to your cart?

ADULT PRODUCTS APPROACH:
- Use proper product names and terminology confidently
- Describe features, materials, and benefits clearly
- Answer questions about products directly and professionally
- Help customers understand product differences and options
- Recommend products based on experience level, preferences, and use case
- Be helpful with sizing, material, and feature questions
- Never be shy or hesitant - customers expect expertise

PRIVACY & DISCRETION:
- Assure customers about discreet shipping (plain packaging, discreet billing)
- Never share customer information
- Maintain privacy in all communications
- Reassure about secure transactions

STORE INFORMATION:
- Website: valleyofthedolls.com.au
- Focus: Adult toys, dolls, and related products
- Commitment: Privacy, discretion, and quality products

ORDER INFORMATION:
When providing order details to customers:
- Present order information clearly and formatted
- Include: Order Number, Status, Date Ordered, Total Amount
- List items in the order with quantities
- Provide tracking information if available
- Always be specific and accurate with dates and amounts
- Format currency properly (e.g., $XX.XX)
- Use line breaks to make information easy to read

When helping customers:
1. Greet them warmly
2. Understand their needs through thoughtful questions
3. Provide relevant product suggestions with details
4. Answer questions comprehensively with clear formatting
5. Assist with orders and account management as needed
6. Always maintain a supportive, non-judgmental atmosphere
7. Present information in an organized, easy-to-read format

Remember: Your goal is to make customers feel comfortable and confident in their purchases while providing excellent, discreet service.";

        return apply_filters('vva_system_prompt', $prompt);
    }

    /**
     * Get response from Gemini AI
     *
     * @param string $user_message User's message
     * @param array $conversation_history Previous messages
     * @param array $context Additional context (products, orders, etc.)
     * @return array|WP_Error Response or error
     */
    public function get_response($user_message, $conversation_history = array(), $context = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Gemini API key is not configured.', 'valley-virtual-assistant'));
        }

        // Build messages for Gemini
        $contents = $this->build_gemini_messages($user_message, $conversation_history, $context);

        // Prepare API request
        $body = array(
            'contents' => $contents,
            'generationConfig' => array(
                'temperature' => (float) get_option('vva_temperature', 0.7),
                'maxOutputTokens' => (int) get_option('vva_max_tokens', 4096),
            ),
            'systemInstruction' => array(
                'parts' => array(
                    array('text' => $this->system_prompt)
                )
            ),
        );

        // Make API request
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent?key=' . $this->api_key;

        $response = wp_remote_post($api_url, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
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
            'content' => $this->extract_gemini_content($data),
            'usage' => isset($data['usageMetadata']) ? $data['usageMetadata'] : array(),
            'model' => $this->model,
            'raw_response' => $data,
        );
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
     * Build messages for Gemini API
     */
    private function build_gemini_messages($user_message, $conversation_history, $context) {
        $contents = array();

        // Add conversation history
        foreach ($conversation_history as $message) {
            $role = ($message['role'] === 'assistant') ? 'model' : 'user';
            $contents[] = array(
                'role' => $role,
                'parts' => array(
                    array('text' => $message['content'])
                )
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
        $contents[] = array(
            'role' => 'user',
            'parts' => array(
                array('text' => $enhanced_message)
            )
        );

        return $contents;
    }

    /**
     * Extract content from Gemini API response
     */
    private function extract_gemini_content($data) {
        if (isset($data['candidates']) && is_array($data['candidates']) && !empty($data['candidates'])) {
            $candidate = $data['candidates'][0];
            if (isset($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
                $text_parts = array();
                foreach ($candidate['content']['parts'] as $part) {
                    if (isset($part['text'])) {
                        $text_parts[] = $part['text'];
                    }
                }
                return implode("\n", $text_parts);
            }
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

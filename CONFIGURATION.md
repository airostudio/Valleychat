# Configuration Guide - Valley Virtual Assistant

## Configuration File Structure

The plugin stores all configuration in the WordPress options table. No separate config files are needed.

## Environment Variables (Optional)

For development or staging environments, you can use environment variables:

```php
// In wp-config.php

// API Configuration
define('VVA_ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY'));

// Plugin Settings
define('VVA_DEBUG_MODE', true);
define('VVA_LOG_LEVEL', 'debug'); // debug, info, warning, error
```

## Settings Reference

### General Settings

#### vva_assistant_name
- **Type**: String
- **Default**: "Valley"
- **Description**: Name of the virtual assistant
- **Example**: "Sophie", "Emma", "Valley"

#### vva_assistant_personality
- **Type**: String
- **Default**: "friendly_professional"
- **Options**: friendly_professional, warm_supportive, efficient_helpful
- **Description**: Personality style of the assistant

#### vva_welcome_message
- **Type**: String
- **Default**: "Hi! How can I help you today?"
- **Description**: First message shown to customers
- **Max Length**: 500 characters

#### vva_assistant_avatar
- **Type**: URL
- **Default**: Plugin default avatar
- **Description**: URL to assistant avatar image
- **Recommended Size**: 200x200px

### AI Configuration

#### vva_ai_provider
- **Type**: String
- **Default**: "anthropic"
- **Options**: anthropic
- **Description**: AI provider (future: openai, cohere)

#### vva_anthropic_api_key
- **Type**: String (encrypted)
- **Default**: Empty
- **Description**: Anthropic API key
- **Format**: "sk-ant-..."
- **Required**: Yes

#### vva_anthropic_model
- **Type**: String
- **Default**: "claude-3-5-sonnet-20241022"
- **Options**:
  - claude-3-5-sonnet-20241022 (recommended)
  - claude-3-opus-20240229
  - claude-3-haiku-20240307
- **Description**: Claude model to use

#### vva_max_tokens
- **Type**: Integer
- **Default**: 4096
- **Range**: 256-8192
- **Description**: Maximum tokens in AI response

#### vva_temperature
- **Type**: Float
- **Default**: 0.7
- **Range**: 0.0-1.0
- **Description**: AI creativity level (0=focused, 1=creative)

### Widget Settings

#### vva_widget_enabled
- **Type**: Boolean
- **Default**: true
- **Description**: Show/hide chat widget

#### vva_widget_position
- **Type**: String
- **Default**: "bottom-right"
- **Options**: bottom-right, bottom-left
- **Description**: Widget position on page

#### vva_primary_color
- **Type**: Color (hex)
- **Default**: "#e91e63"
- **Description**: Main widget color
- **Example**: "#FF5722"

#### vva_secondary_color
- **Type**: Color (hex)
- **Default**: "#f8bbd0"
- **Description**: Secondary accent color

### Privacy Settings

#### vva_data_retention_days
- **Type**: Integer
- **Default**: 90
- **Range**: 0-365 (0 = forever)
- **Description**: Days to keep conversation data

#### vva_encryption_enabled
- **Type**: Boolean
- **Default**: true
- **Description**: Encrypt conversation data

#### vva_log_conversations
- **Type**: Boolean
- **Default**: true
- **Description**: Save conversation history

#### vva_anonymous_mode
- **Type**: Boolean
- **Default**: false
- **Description**: Allow anonymous chat (guests)

### Feature Settings

#### vva_order_tracking
- **Type**: Boolean
- **Default**: true
- **Description**: Enable order tracking feature

#### vva_product_recommendations
- **Type**: Boolean
- **Default**: true
- **Description**: Enable AI product recommendations

#### vva_customer_service
- **Type**: Boolean
- **Default**: true
- **Description**: Enable customer service features

#### vva_proactive_assistance
- **Type**: Boolean
- **Default**: true
- **Description**: Enable proactive help triggers

### Advanced Settings

#### vva_debug_mode
- **Type**: Boolean
- **Default**: false
- **Description**: Enable debug logging

#### vva_rate_limit_enabled
- **Type**: Boolean
- **Default**: true
- **Description**: Enable API rate limiting

#### vva_rate_limit_requests
- **Type**: Integer
- **Default**: 60
- **Description**: Max requests per minute

## Programmatic Configuration

### Update Settings via Code

```php
// Update a single setting
update_option('vva_assistant_name', 'Sophie');

// Update multiple settings
$settings = array(
    'vva_assistant_name' => 'Sophie',
    'vva_primary_color' => '#FF5722',
    'vva_widget_position' => 'bottom-left'
);

foreach ($settings as $key => $value) {
    update_option($key, $value);
}
```

### Get Settings via Code

```php
// Get a single setting
$assistant_name = get_option('vva_assistant_name', 'Valley');

// Get with type casting
$enabled = (bool) get_option('vva_widget_enabled', true);
$max_tokens = (int) get_option('vva_max_tokens', 4096);
```

### Filters for Configuration

```php
// Modify system prompt
add_filter('vva_system_prompt', function($prompt) {
    return $prompt . "\nAdditional instructions here.";
});

// Modify welcome message
add_filter('vva_welcome_message', function($message) {
    return "Welcome! " . $message;
});

// Modify FAQ content
add_filter('vva_faq_items', function($faq) {
    $faq[] = array(
        'question' => 'Custom question?',
        'answer' => 'Custom answer.',
        'category' => 'custom'
    );
    return $faq;
});
```

### Actions for Events

```php
// When conversation starts
add_action('vva_conversation_started', function($conversation_id) {
    error_log("Conversation {$conversation_id} started");
});

// When message is sent
add_action('vva_message_sent', function($conversation_id, $message) {
    // Custom tracking
}, 10, 2);

// When product is recommended
add_action('vva_product_recommended', function($product_id, $conversation_id) {
    // Analytics tracking
}, 10, 2);
```

## Configuration Examples

### High-Traffic Store

```php
// Optimize for speed and cost
update_option('vva_anthropic_model', 'claude-3-haiku-20240307');
update_option('vva_max_tokens', 2048);
update_option('vva_rate_limit_enabled', true);
update_option('vva_rate_limit_requests', 100);
```

### Privacy-Focused Store

```php
// Maximum privacy settings
update_option('vva_data_retention_days', 30);
update_option('vva_encryption_enabled', true);
update_option('vva_anonymous_mode', true);
update_option('vva_log_conversations', false);
```

### Luxury Brand Store

```php
// Premium experience
update_option('vva_anthropic_model', 'claude-3-opus-20240229');
update_option('vva_max_tokens', 8192);
update_option('vva_temperature', 0.8);
update_option('vva_assistant_personality', 'warm_supportive');
```

## Database Schema

### Conversations Table

```sql
CREATE TABLE wp_vva_conversations (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    session_id varchar(255) NOT NULL,
    user_id bigint(20) DEFAULT NULL,
    customer_email varchar(255) DEFAULT NULL,
    started_at datetime DEFAULT CURRENT_TIMESTAMP,
    ended_at datetime DEFAULT NULL,
    status varchar(20) DEFAULT 'active',
    PRIMARY KEY (id),
    KEY session_id (session_id),
    KEY user_id (user_id)
);
```

### Messages Table

```sql
CREATE TABLE wp_vva_messages (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    conversation_id bigint(20) NOT NULL,
    role varchar(20) NOT NULL,
    content longtext NOT NULL,
    metadata longtext DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY conversation_id (conversation_id)
);
```

## API Integration

### Anthropic API Configuration

```php
// API endpoint
$api_url = 'https://api.anthropic.com/v1/messages';

// Request headers
$headers = array(
    'Content-Type' => 'application/json',
    'x-api-key' => get_option('vva_anthropic_api_key'),
    'anthropic-version' => '2023-06-01'
);

// Request body
$body = array(
    'model' => get_option('vva_anthropic_model'),
    'max_tokens' => (int) get_option('vva_max_tokens'),
    'temperature' => (float) get_option('vva_temperature'),
    'messages' => $messages
);
```

## Performance Tuning

### Cache Configuration

```php
// Enable object caching
add_filter('vva_enable_cache', '__return_true');

// Cache duration (seconds)
add_filter('vva_cache_duration', function() {
    return 3600; // 1 hour
});
```

### Rate Limiting

```php
// Custom rate limit
add_filter('vva_rate_limit', function($limit) {
    return 120; // 120 requests per minute
});

// Rate limit window
add_filter('vva_rate_limit_window', function($window) {
    return 60; // 60 seconds
});
```

## Monitoring & Logging

### Enable Detailed Logging

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// In plugin settings
update_option('vva_debug_mode', true);
```

### Custom Logging

```php
// Log to custom file
add_action('vva_log', function($message, $level) {
    $log_file = WP_CONTENT_DIR . '/vva-custom.log';
    $timestamp = date('Y-m-d H:i:s');
    error_log("[{$timestamp}] [{$level}] {$message}\n", 3, $log_file);
}, 10, 2);
```

## Security Configuration

### API Key Rotation

```php
// Rotate API key
function rotate_vva_api_key($new_key) {
    $old_key = get_option('vva_anthropic_api_key');

    // Store old key in history
    $key_history = get_option('vva_api_key_history', array());
    $key_history[] = array(
        'key' => $old_key,
        'rotated_at' => current_time('mysql')
    );
    update_option('vva_api_key_history', $key_history);

    // Update to new key
    update_option('vva_anthropic_api_key', $new_key);
}
```

### IP Whitelisting (optional)

```php
// Restrict admin access
add_filter('vva_admin_ip_whitelist', function($ips) {
    return array(
        '192.168.1.1',
        '10.0.0.1'
    );
});
```

## Backup & Restore

### Export Configuration

```php
function export_vva_config() {
    $config = array();

    $options = array(
        'vva_assistant_name',
        'vva_primary_color',
        'vva_widget_position',
        // ... all options
    );

    foreach ($options as $option) {
        $config[$option] = get_option($option);
    }

    return json_encode($config, JSON_PRETTY_PRINT);
}
```

### Import Configuration

```php
function import_vva_config($json_config) {
    $config = json_decode($json_config, true);

    foreach ($config as $key => $value) {
        update_option($key, $value);
    }
}
```

---

For more information, see README.md and INSTALLATION.md

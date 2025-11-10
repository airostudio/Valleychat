<?php
/**
 * Valley Virtual Assistant - Settings
 *
 * @package Valley_Virtual_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VVA_Settings Class
 */
class VVA_Settings {

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
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // General Settings
        register_setting('vva_general_settings', 'vva_assistant_name');
        register_setting('vva_general_settings', 'vva_assistant_personality');
        register_setting('vva_general_settings', 'vva_assistant_avatar');
        register_setting('vva_general_settings', 'vva_welcome_message');

        // AI Settings
        register_setting('vva_ai_settings', 'vva_openai_api_key');
        register_setting('vva_ai_settings', 'vva_openai_model');
        register_setting('vva_ai_settings', 'vva_max_tokens');
        register_setting('vva_ai_settings', 'vva_temperature');

        // Widget Settings
        register_setting('vva_widget_settings', 'vva_widget_enabled');
        register_setting('vva_widget_settings', 'vva_widget_position');
        register_setting('vva_widget_settings', 'vva_primary_color');
        register_setting('vva_widget_settings', 'vva_secondary_color');

        // Privacy Settings
        register_setting('vva_privacy_settings', 'vva_data_retention_days');
        register_setting('vva_privacy_settings', 'vva_anonymous_mode');
        register_setting('vva_privacy_settings', 'vva_encryption_enabled');
        register_setting('vva_privacy_settings', 'vva_log_conversations');

        // Feature Settings
        register_setting('vva_feature_settings', 'vva_order_tracking');
        register_setting('vva_feature_settings', 'vva_product_recommendations');
        register_setting('vva_feature_settings', 'vva_customer_service');
        register_setting('vva_feature_settings', 'vva_proactive_assistance');
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h1><?php _e('Valley Virtual Assistant - Settings', 'valley-virtual-assistant'); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=valley-virtual-assistant-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General', 'valley-virtual-assistant'); ?>
                </a>
                <a href="?page=valley-virtual-assistant-settings&tab=ai" class="nav-tab <?php echo $active_tab === 'ai' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('AI Configuration', 'valley-virtual-assistant'); ?>
                </a>
                <a href="?page=valley-virtual-assistant-settings&tab=widget" class="nav-tab <?php echo $active_tab === 'widget' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Widget', 'valley-virtual-assistant'); ?>
                </a>
                <a href="?page=valley-virtual-assistant-settings&tab=features" class="nav-tab <?php echo $active_tab === 'features' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Features', 'valley-virtual-assistant'); ?>
                </a>
                <a href="?page=valley-virtual-assistant-settings&tab=privacy" class="nav-tab <?php echo $active_tab === 'privacy' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Privacy', 'valley-virtual-assistant'); ?>
                </a>
            </h2>

            <form method="post" action="options.php">
                <?php
                switch ($active_tab) {
                    case 'general':
                        settings_fields('vva_general_settings');
                        $this->render_general_settings();
                        break;
                    case 'ai':
                        settings_fields('vva_ai_settings');
                        $this->render_ai_settings();
                        break;
                    case 'widget':
                        settings_fields('vva_widget_settings');
                        $this->render_widget_settings();
                        break;
                    case 'features':
                        settings_fields('vva_feature_settings');
                        $this->render_feature_settings();
                        break;
                    case 'privacy':
                        settings_fields('vva_privacy_settings');
                        $this->render_privacy_settings();
                        break;
                }
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render general settings
     */
    private function render_general_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="vva_assistant_name"><?php _e('Assistant Name', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <input type="text" id="vva_assistant_name" name="vva_assistant_name" value="<?php echo esc_attr(get_option('vva_assistant_name', 'Valley')); ?>" class="regular-text">
                    <p class="description"><?php _e('The name of your virtual assistant (e.g., Valley, Sophie, Emma)', 'valley-virtual-assistant'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="vva_assistant_personality"><?php _e('Personality', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <select id="vva_assistant_personality" name="vva_assistant_personality">
                        <option value="friendly_professional" <?php selected(get_option('vva_assistant_personality'), 'friendly_professional'); ?>>
                            <?php _e('Friendly & Professional', 'valley-virtual-assistant'); ?>
                        </option>
                        <option value="warm_supportive" <?php selected(get_option('vva_assistant_personality'), 'warm_supportive'); ?>>
                            <?php _e('Warm & Supportive', 'valley-virtual-assistant'); ?>
                        </option>
                        <option value="efficient_helpful" <?php selected(get_option('vva_assistant_personality'), 'efficient_helpful'); ?>>
                            <?php _e('Efficient & Helpful', 'valley-virtual-assistant'); ?>
                        </option>
                    </select>
                    <p class="description"><?php _e('Choose the personality style for your assistant', 'valley-virtual-assistant'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="vva_welcome_message"><?php _e('Welcome Message', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <textarea id="vva_welcome_message" name="vva_welcome_message" rows="3" class="large-text"><?php echo esc_textarea(get_option('vva_welcome_message', 'Hi! How can I help you today?')); ?></textarea>
                    <p class="description"><?php _e('The first message customers see when they open the chat', 'valley-virtual-assistant'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="vva_assistant_avatar"><?php _e('Avatar URL', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <input type="url" id="vva_assistant_avatar" name="vva_assistant_avatar" value="<?php echo esc_url(get_option('vva_assistant_avatar', '')); ?>" class="large-text">
                    <p class="description"><?php _e('URL to the assistant avatar image', 'valley-virtual-assistant'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render AI settings
     */
    private function render_ai_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="vva_openai_api_key"><?php _e('OpenAI API Key', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <input type="password" id="vva_openai_api_key" name="vva_openai_api_key" value="<?php echo esc_attr(get_option('vva_openai_api_key', '')); ?>" class="large-text">
                    <p class="description">
                        <?php _e('Get your API key from', 'valley-virtual-assistant'); ?>
                        <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="vva_openai_model"><?php _e('Model', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <select id="vva_openai_model" name="vva_openai_model">
                        <option value="gpt-3.5-turbo" <?php selected(get_option('vva_openai_model', 'gpt-3.5-turbo'), 'gpt-3.5-turbo'); ?>>
                            GPT-3.5 Turbo (Recommended - Fast & Cost-Effective)
                        </option>
                        <option value="gpt-4" <?php selected(get_option('vva_openai_model', 'gpt-3.5-turbo'), 'gpt-4'); ?>>
                            GPT-4 (Most Capable)
                        </option>
                        <option value="gpt-4-turbo" <?php selected(get_option('vva_openai_model', 'gpt-3.5-turbo'), 'gpt-4-turbo'); ?>>
                            GPT-4 Turbo (Latest & Faster)
                        </option>
                        <option value="gpt-4o" <?php selected(get_option('vva_openai_model', 'gpt-3.5-turbo'), 'gpt-4o'); ?>>
                            GPT-4o (Omni - Multimodal)
                        </option>
                    </select>
                    <p class="description"><?php _e('Choose the ChatGPT model - GPT-3.5 Turbo is fast and cost-effective', 'valley-virtual-assistant'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="vva_max_tokens"><?php _e('Max Tokens', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <input type="number" id="vva_max_tokens" name="vva_max_tokens" value="<?php echo esc_attr(get_option('vva_max_tokens', 4096)); ?>" min="256" max="8192" step="256">
                    <p class="description"><?php _e('Maximum tokens for AI responses (256-8192)', 'valley-virtual-assistant'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="vva_temperature"><?php _e('Temperature', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <input type="number" id="vva_temperature" name="vva_temperature" value="<?php echo esc_attr(get_option('vva_temperature', 0.7)); ?>" min="0" max="1" step="0.1">
                    <p class="description"><?php _e('Creativity level (0.0 = focused, 1.0 = creative)', 'valley-virtual-assistant'); ?></p>
                </td>
            </tr>
        </table>
        <div class="vva-test-connection">
            <button type="button" id="vva-test-api-connection" class="button button-secondary">
                <?php _e('Test Connection', 'valley-virtual-assistant'); ?>
            </button>
            <span id="vva-test-result"></span>
        </div>
        <?php
    }

    /**
     * Render widget settings
     */
    private function render_widget_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="vva_widget_enabled"><?php _e('Enable Widget', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="vva_widget_enabled" name="vva_widget_enabled" value="1" <?php checked(get_option('vva_widget_enabled', true)); ?>>
                        <?php _e('Show chat widget on frontend', 'valley-virtual-assistant'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="vva_widget_position"><?php _e('Widget Position', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <select id="vva_widget_position" name="vva_widget_position">
                        <option value="bottom-right" <?php selected(get_option('vva_widget_position'), 'bottom-right'); ?>>
                            <?php _e('Bottom Right', 'valley-virtual-assistant'); ?>
                        </option>
                        <option value="bottom-left" <?php selected(get_option('vva_widget_position'), 'bottom-left'); ?>>
                            <?php _e('Bottom Left', 'valley-virtual-assistant'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="vva_primary_color"><?php _e('Primary Color', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <input type="color" id="vva_primary_color" name="vva_primary_color" value="<?php echo esc_attr(get_option('vva_primary_color', '#e91e63')); ?>">
                    <p class="description"><?php _e('Main color for the chat widget', 'valley-virtual-assistant'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="vva_secondary_color"><?php _e('Secondary Color', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <input type="color" id="vva_secondary_color" name="vva_secondary_color" value="<?php echo esc_attr(get_option('vva_secondary_color', '#f8bbd0')); ?>">
                    <p class="description"><?php _e('Secondary color for accents', 'valley-virtual-assistant'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render feature settings
     */
    private function render_feature_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enabled Features', 'valley-virtual-assistant'); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="vva_order_tracking" value="1" <?php checked(get_option('vva_order_tracking', true)); ?>>
                            <?php _e('Order Tracking & Status', 'valley-virtual-assistant'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="vva_product_recommendations" value="1" <?php checked(get_option('vva_product_recommendations', true)); ?>>
                            <?php _e('Product Recommendations', 'valley-virtual-assistant'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="vva_customer_service" value="1" <?php checked(get_option('vva_customer_service', true)); ?>>
                            <?php _e('Customer Service & FAQ', 'valley-virtual-assistant'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="vva_proactive_assistance" value="1" <?php checked(get_option('vva_proactive_assistance', true)); ?>>
                            <?php _e('Proactive Assistance', 'valley-virtual-assistant'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render privacy settings
     */
    private function render_privacy_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="vva_data_retention_days"><?php _e('Data Retention', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <input type="number" id="vva_data_retention_days" name="vva_data_retention_days" value="<?php echo esc_attr(get_option('vva_data_retention_days', 90)); ?>" min="0" max="365">
                    <?php _e('days', 'valley-virtual-assistant'); ?>
                    <p class="description"><?php _e('How long to keep conversation data (0 = forever)', 'valley-virtual-assistant'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="vva_encryption_enabled"><?php _e('Data Encryption', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="vva_encryption_enabled" name="vva_encryption_enabled" value="1" <?php checked(get_option('vva_encryption_enabled', true)); ?>>
                        <?php _e('Encrypt sensitive conversation data', 'valley-virtual-assistant'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="vva_log_conversations"><?php _e('Conversation Logging', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="vva_log_conversations" name="vva_log_conversations" value="1" <?php checked(get_option('vva_log_conversations', true)); ?>>
                        <?php _e('Save conversation history for improvement and support', 'valley-virtual-assistant'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="vva_anonymous_mode"><?php _e('Anonymous Mode', 'valley-virtual-assistant'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="vva_anonymous_mode" name="vva_anonymous_mode" value="1" <?php checked(get_option('vva_anonymous_mode', false)); ?>>
                        <?php _e('Allow anonymous chatting (guest users)', 'valley-virtual-assistant'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }
}

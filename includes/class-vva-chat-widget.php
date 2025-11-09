<?php
/**
 * Valley Virtual Assistant - Chat Widget
 *
 * @package Valley_Virtual_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VVA_Chat_Widget Class
 */
class VVA_Chat_Widget {

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
        add_action('wp_footer', array($this, 'render_widget'));
    }

    /**
     * Render chat widget
     */
    public function render_widget() {
        if (!get_option('vva_widget_enabled', true)) {
            return;
        }

        // Don't show on admin pages
        if (is_admin()) {
            return;
        }

        $this->render_widget_html();
    }

    /**
     * Render widget HTML
     */
    private function render_widget_html() {
        $assistant_name = get_option('vva_assistant_name', 'Valley');
        $avatar = get_option('vva_assistant_avatar', VVA_PLUGIN_URL . 'assets/images/avatar-default.png');
        $position = get_option('vva_widget_position', 'bottom-right');
        ?>
        <div id="vva-chat-widget" class="vva-widget-<?php echo esc_attr($position); ?>">
            <!-- Chat Toggle Button -->
            <button id="vva-chat-toggle" class="vva-chat-toggle" aria-label="Open chat">
                <svg class="vva-icon-chat" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <svg class="vva-icon-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <!-- Chat Window -->
            <div id="vva-chat-window" class="vva-chat-window" style="display: none;">
                <!-- Header -->
                <div class="vva-chat-header">
                    <div class="vva-assistant-info">
                        <img src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($assistant_name); ?>" class="vva-assistant-avatar">
                        <div class="vva-assistant-details">
                            <h3 class="vva-assistant-name"><?php echo esc_html($assistant_name); ?></h3>
                            <span class="vva-assistant-status">
                                <span class="vva-status-dot"></span>
                                Online
                            </span>
                        </div>
                    </div>
                    <button class="vva-minimize" aria-label="Minimize chat">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                </div>

                <!-- Messages Container -->
                <div id="vva-messages-container" class="vva-messages-container">
                    <div class="vva-message vva-message-assistant">
                        <img src="<?php echo esc_url($avatar); ?>" alt="" class="vva-message-avatar">
                        <div class="vva-message-content">
                            <p><?php echo esc_html(get_option('vva_welcome_message', 'Hi! How can I help you today?')); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Typing Indicator -->
                <div id="vva-typing-indicator" class="vva-typing-indicator" style="display: none;">
                    <img src="<?php echo esc_url($avatar); ?>" alt="" class="vva-message-avatar">
                    <div class="vva-typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="vva-chat-input-area">
                    <form id="vva-chat-form">
                        <input
                            type="text"
                            id="vva-message-input"
                            class="vva-message-input"
                            placeholder="Type your message..."
                            autocomplete="off"
                        >
                        <button type="submit" class="vva-send-button" aria-label="Send message">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </button>
                    </form>
                    <div class="vva-privacy-note">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Your privacy is protected
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

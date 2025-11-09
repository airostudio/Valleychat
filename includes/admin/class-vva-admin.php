<?php
/**
 * Valley Virtual Assistant - Admin Interface
 *
 * @package Valley_Virtual_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VVA_Admin Class
 */
class VVA_Admin {

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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Valley Virtual Assistant', 'valley-virtual-assistant'),
            __('Virtual Assistant', 'valley-virtual-assistant'),
            'manage_options',
            'valley-virtual-assistant',
            array($this, 'render_dashboard'),
            'dashicons-format-chat',
            56
        );

        add_submenu_page(
            'valley-virtual-assistant',
            __('Dashboard', 'valley-virtual-assistant'),
            __('Dashboard', 'valley-virtual-assistant'),
            'manage_options',
            'valley-virtual-assistant',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'valley-virtual-assistant',
            __('Settings', 'valley-virtual-assistant'),
            __('Settings', 'valley-virtual-assistant'),
            'manage_options',
            'valley-virtual-assistant-settings',
            array($this, 'render_settings')
        );

        add_submenu_page(
            'valley-virtual-assistant',
            __('Conversations', 'valley-virtual-assistant'),
            __('Conversations', 'valley-virtual-assistant'),
            'manage_options',
            'valley-virtual-assistant-conversations',
            array($this, 'render_conversations')
        );

        add_submenu_page(
            'valley-virtual-assistant',
            __('Analytics', 'valley-virtual-assistant'),
            __('Analytics', 'valley-virtual-assistant'),
            'manage_options',
            'valley-virtual-assistant-analytics',
            array($this, 'render_analytics')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Settings are registered in VVA_Settings class
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        global $wpdb;

        // Get statistics
        $conversations_table = $wpdb->prefix . 'vva_conversations';
        $messages_table = $wpdb->prefix . 'vva_messages';

        $total_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table");
        $active_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table WHERE status = 'active'");
        $total_messages = $wpdb->get_var("SELECT COUNT(*) FROM $messages_table");

        // Get recent conversations
        $recent_conversations = $wpdb->get_results("
            SELECT c.*, COUNT(m.id) as message_count
            FROM $conversations_table c
            LEFT JOIN $messages_table m ON c.id = m.conversation_id
            GROUP BY c.id
            ORDER BY c.started_at DESC
            LIMIT 10
        ");

        ?>
        <div class="wrap">
            <h1><?php _e('Valley Virtual Assistant - Dashboard', 'valley-virtual-assistant'); ?></h1>

            <div class="vva-dashboard">
                <!-- Stats Cards -->
                <div class="vva-stats-grid">
                    <div class="vva-stat-card">
                        <div class="vva-stat-icon">
                            <span class="dashicons dashicons-format-chat"></span>
                        </div>
                        <div class="vva-stat-content">
                            <h3><?php echo number_format($total_conversations); ?></h3>
                            <p><?php _e('Total Conversations', 'valley-virtual-assistant'); ?></p>
                        </div>
                    </div>

                    <div class="vva-stat-card">
                        <div class="vva-stat-icon">
                            <span class="dashicons dashicons-admin-comments"></span>
                        </div>
                        <div class="vva-stat-content">
                            <h3><?php echo number_format($total_messages); ?></h3>
                            <p><?php _e('Total Messages', 'valley-virtual-assistant'); ?></p>
                        </div>
                    </div>

                    <div class="vva-stat-card">
                        <div class="vva-stat-icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <div class="vva-stat-content">
                            <h3><?php echo number_format($active_conversations); ?></h3>
                            <p><?php _e('Active Conversations', 'valley-virtual-assistant'); ?></p>
                        </div>
                    </div>

                    <div class="vva-stat-card">
                        <div class="vva-stat-icon">
                            <span class="dashicons dashicons-chart-area"></span>
                        </div>
                        <div class="vva-stat-content">
                            <h3><?php echo $total_conversations > 0 ? number_format($total_messages / $total_conversations, 1) : '0'; ?></h3>
                            <p><?php _e('Avg. Messages per Conversation', 'valley-virtual-assistant'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="vva-quick-actions">
                    <h2><?php _e('Quick Actions', 'valley-virtual-assistant'); ?></h2>
                    <div class="vva-actions-grid">
                        <a href="<?php echo admin_url('admin.php?page=valley-virtual-assistant-settings'); ?>" class="vva-action-button">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php _e('Configure Settings', 'valley-virtual-assistant'); ?>
                        </a>
                        <button id="vva-test-api" class="vva-action-button">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Test AI Connection', 'valley-virtual-assistant'); ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=valley-virtual-assistant-conversations'); ?>" class="vva-action-button">
                            <span class="dashicons dashicons-list-view"></span>
                            <?php _e('View Conversations', 'valley-virtual-assistant'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=valley-virtual-assistant-analytics'); ?>" class="vva-action-button">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php _e('View Analytics', 'valley-virtual-assistant'); ?>
                        </a>
                    </div>
                </div>

                <!-- Recent Conversations -->
                <div class="vva-recent-conversations">
                    <h2><?php _e('Recent Conversations', 'valley-virtual-assistant'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'valley-virtual-assistant'); ?></th>
                                <th><?php _e('Customer', 'valley-virtual-assistant'); ?></th>
                                <th><?php _e('Messages', 'valley-virtual-assistant'); ?></th>
                                <th><?php _e('Started', 'valley-virtual-assistant'); ?></th>
                                <th><?php _e('Status', 'valley-virtual-assistant'); ?></th>
                                <th><?php _e('Actions', 'valley-virtual-assistant'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_conversations)) : ?>
                                <?php foreach ($recent_conversations as $conversation) : ?>
                                    <tr>
                                        <td><?php echo $conversation->id; ?></td>
                                        <td>
                                            <?php
                                            if ($conversation->customer_email) {
                                                echo esc_html($conversation->customer_email);
                                            } else {
                                                echo '<em>' . __('Guest', 'valley-virtual-assistant') . '</em>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo $conversation->message_count; ?></td>
                                        <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conversation->started_at)); ?></td>
                                        <td>
                                            <span class="vva-status vva-status-<?php echo esc_attr($conversation->status); ?>">
                                                <?php echo ucfirst($conversation->status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo admin_url('admin.php?page=valley-virtual-assistant-conversations&conversation_id=' . $conversation->id); ?>">
                                                <?php _e('View', 'valley-virtual-assistant'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6"><?php _e('No conversations yet.', 'valley-virtual-assistant'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- System Status -->
                <div class="vva-system-status">
                    <h2><?php _e('System Status', 'valley-virtual-assistant'); ?></h2>
                    <table class="wp-list-table widefat">
                        <tbody>
                            <tr>
                                <td><?php _e('Plugin Version', 'valley-virtual-assistant'); ?></td>
                                <td><?php echo VVA_VERSION; ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('WordPress Version', 'valley-virtual-assistant'); ?></td>
                                <td><?php echo get_bloginfo('version'); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('WooCommerce Status', 'valley-virtual-assistant'); ?></td>
                                <td>
                                    <?php if (class_exists('WooCommerce')) : ?>
                                        <span style="color: green;">✓ <?php _e('Active', 'valley-virtual-assistant'); ?> (v<?php echo WC()->version; ?>)</span>
                                    <?php else : ?>
                                        <span style="color: red;">✗ <?php _e('Not Active', 'valley-virtual-assistant'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php _e('AI Provider', 'valley-virtual-assistant'); ?></td>
                                <td><?php echo ucfirst(get_option('vva_ai_provider', 'anthropic')); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('API Key Status', 'valley-virtual-assistant'); ?></td>
                                <td>
                                    <?php if (get_option('vva_anthropic_api_key')) : ?>
                                        <span style="color: green;">✓ <?php _e('Configured', 'valley-virtual-assistant'); ?></span>
                                    <?php else : ?>
                                        <span style="color: red;">✗ <?php _e('Not Configured', 'valley-virtual-assistant'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings() {
        // Settings are rendered by VVA_Settings class
        VVA_Settings::instance()->render_settings_page();
    }

    /**
     * Render conversations page
     */
    public function render_conversations() {
        global $wpdb;

        $conversation_id = isset($_GET['conversation_id']) ? absint($_GET['conversation_id']) : 0;

        if ($conversation_id) {
            $this->render_single_conversation($conversation_id);
            return;
        }

        // List all conversations
        $conversations_table = $wpdb->prefix . 'vva_conversations';
        $messages_table = $wpdb->prefix . 'vva_messages';

        $conversations = $wpdb->get_results("
            SELECT c.*, COUNT(m.id) as message_count
            FROM $conversations_table c
            LEFT JOIN $messages_table m ON c.id = m.conversation_id
            GROUP BY c.id
            ORDER BY c.started_at DESC
            LIMIT 100
        ");

        ?>
        <div class="wrap">
            <h1><?php _e('Conversations', 'valley-virtual-assistant'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'valley-virtual-assistant'); ?></th>
                        <th><?php _e('Customer', 'valley-virtual-assistant'); ?></th>
                        <th><?php _e('Messages', 'valley-virtual-assistant'); ?></th>
                        <th><?php _e('Started', 'valley-virtual-assistant'); ?></th>
                        <th><?php _e('Ended', 'valley-virtual-assistant'); ?></th>
                        <th><?php _e('Status', 'valley-virtual-assistant'); ?></th>
                        <th><?php _e('Actions', 'valley-virtual-assistant'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($conversations as $conversation) : ?>
                        <tr>
                            <td><?php echo $conversation->id; ?></td>
                            <td><?php echo $conversation->customer_email ? esc_html($conversation->customer_email) : '<em>Guest</em>'; ?></td>
                            <td><?php echo $conversation->message_count; ?></td>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conversation->started_at)); ?></td>
                            <td><?php echo $conversation->ended_at ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conversation->ended_at)) : '-'; ?></td>
                            <td><?php echo ucfirst($conversation->status); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=valley-virtual-assistant-conversations&conversation_id=' . $conversation->id); ?>">
                                    <?php _e('View', 'valley-virtual-assistant'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render single conversation
     */
    private function render_single_conversation($conversation_id) {
        global $wpdb;

        $conversations_table = $wpdb->prefix . 'vva_conversations';
        $messages_table = $wpdb->prefix . 'vva_messages';

        $conversation = $wpdb->get_row($wpdb->prepare("SELECT * FROM $conversations_table WHERE id = %d", $conversation_id));

        if (!$conversation) {
            echo '<div class="wrap"><p>' . __('Conversation not found.', 'valley-virtual-assistant') . '</p></div>';
            return;
        }

        $messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM $messages_table WHERE conversation_id = %d ORDER BY id ASC", $conversation_id));

        ?>
        <div class="wrap">
            <h1><?php _e('Conversation Details', 'valley-virtual-assistant'); ?></h1>
            <p>
                <a href="<?php echo admin_url('admin.php?page=valley-virtual-assistant-conversations'); ?>">
                    &larr; <?php _e('Back to conversations', 'valley-virtual-assistant'); ?>
                </a>
            </p>

            <div class="vva-conversation-details">
                <div class="vva-conversation-meta">
                    <p><strong><?php _e('ID:', 'valley-virtual-assistant'); ?></strong> <?php echo $conversation->id; ?></p>
                    <p><strong><?php _e('Customer:', 'valley-virtual-assistant'); ?></strong> <?php echo $conversation->customer_email ?: 'Guest'; ?></p>
                    <p><strong><?php _e('Started:', 'valley-virtual-assistant'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conversation->started_at)); ?></p>
                    <p><strong><?php _e('Status:', 'valley-virtual-assistant'); ?></strong> <?php echo ucfirst($conversation->status); ?></p>
                </div>

                <div class="vva-conversation-messages">
                    <?php foreach ($messages as $message) : ?>
                        <div class="vva-message vva-message-<?php echo esc_attr($message->role); ?>">
                            <div class="vva-message-header">
                                <strong><?php echo ucfirst($message->role); ?></strong>
                                <span class="vva-message-time"><?php echo date_i18n(get_option('time_format'), strtotime($message->created_at)); ?></span>
                            </div>
                            <div class="vva-message-content">
                                <?php echo nl2br(esc_html($message->content)); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render analytics page
     */
    public function render_analytics() {
        VVA_Analytics::instance()->render_analytics_page();
    }
}

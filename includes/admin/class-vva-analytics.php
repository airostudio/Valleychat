<?php
/**
 * Valley Virtual Assistant - Analytics
 *
 * @package Valley_Virtual_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VVA_Analytics Class
 */
class VVA_Analytics {

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
        // Constructor
    }

    /**
     * Track event
     */
    public static function track_event($event_type, $data = array()) {
        global $wpdb;

        $table = $wpdb->prefix . 'vva_analytics';

        $wpdb->insert($table, array(
            'event_type' => $event_type,
            'event_data' => json_encode($data),
            'session_id' => isset($_COOKIE['vva_session_id']) ? $_COOKIE['vva_session_id'] : '',
            'user_id' => get_current_user_id() ?: null,
            'created_at' => current_time('mysql'),
        ));
    }

    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        global $wpdb;

        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '7days';

        // Calculate date range
        switch ($period) {
            case '24hours':
                $start_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
                break;
            case '7days':
                $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
            case '30days':
                $start_date = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
            case '90days':
                $start_date = date('Y-m-d H:i:s', strtotime('-90 days'));
                break;
            default:
                $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        }

        $conversations_table = $wpdb->prefix . 'vva_conversations';
        $messages_table = $wpdb->prefix . 'vva_messages';
        $analytics_table = $wpdb->prefix . 'vva_analytics';

        // Get stats
        $total_conversations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $conversations_table WHERE started_at >= %s",
            $start_date
        ));

        $total_messages = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $messages_table WHERE created_at >= %s",
            $start_date
        ));

        $avg_conversation_length = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(msg_count) FROM (
                SELECT COUNT(*) as msg_count
                FROM $messages_table
                WHERE created_at >= %s
                GROUP BY conversation_id
            ) as counts",
            $start_date
        ));

        // Get conversation volume by day
        $daily_conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(started_at) as date, COUNT(*) as count
            FROM $conversations_table
            WHERE started_at >= %s
            GROUP BY DATE(started_at)
            ORDER BY date ASC",
            $start_date
        ));

        ?>
        <div class="wrap">
            <h1><?php _e('Analytics', 'valley-virtual-assistant'); ?></h1>

            <!-- Period Selector -->
            <div class="vva-period-selector">
                <a href="?page=valley-virtual-assistant-analytics&period=24hours" class="button <?php echo $period === '24hours' ? 'button-primary' : ''; ?>">
                    <?php _e('24 Hours', 'valley-virtual-assistant'); ?>
                </a>
                <a href="?page=valley-virtual-assistant-analytics&period=7days" class="button <?php echo $period === '7days' ? 'button-primary' : ''; ?>">
                    <?php _e('7 Days', 'valley-virtual-assistant'); ?>
                </a>
                <a href="?page=valley-virtual-assistant-analytics&period=30days" class="button <?php echo $period === '30days' ? 'button-primary' : ''; ?>">
                    <?php _e('30 Days', 'valley-virtual-assistant'); ?>
                </a>
                <a href="?page=valley-virtual-assistant-analytics&period=90days" class="button <?php echo $period === '90days' ? 'button-primary' : ''; ?>">
                    <?php _e('90 Days', 'valley-virtual-assistant'); ?>
                </a>
            </div>

            <!-- Stats Grid -->
            <div class="vva-stats-grid">
                <div class="vva-stat-card">
                    <h3><?php echo number_format($total_conversations); ?></h3>
                    <p><?php _e('Total Conversations', 'valley-virtual-assistant'); ?></p>
                </div>
                <div class="vva-stat-card">
                    <h3><?php echo number_format($total_messages); ?></h3>
                    <p><?php _e('Total Messages', 'valley-virtual-assistant'); ?></p>
                </div>
                <div class="vva-stat-card">
                    <h3><?php echo number_format($avg_conversation_length, 1); ?></h3>
                    <p><?php _e('Avg. Messages per Conversation', 'valley-virtual-assistant'); ?></p>
                </div>
                <div class="vva-stat-card">
                    <h3><?php echo $total_conversations > 0 ? number_format(($total_messages / $total_conversations), 1) : '0'; ?></h3>
                    <p><?php _e('Engagement Rate', 'valley-virtual-assistant'); ?></p>
                </div>
            </div>

            <!-- Daily Conversation Chart -->
            <div class="vva-chart-container">
                <h2><?php _e('Conversation Volume', 'valley-virtual-assistant'); ?></h2>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'valley-virtual-assistant'); ?></th>
                            <th><?php _e('Conversations', 'valley-virtual-assistant'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_conversations as $day) : ?>
                            <tr>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($day->date)); ?></td>
                                <td><?php echo number_format($day->count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Events -->
            <?php
            $top_events = $wpdb->get_results($wpdb->prepare(
                "SELECT event_type, COUNT(*) as count
                FROM $analytics_table
                WHERE created_at >= %s
                GROUP BY event_type
                ORDER BY count DESC
                LIMIT 10",
                $start_date
            ));
            ?>

            <div class="vva-top-events">
                <h2><?php _e('Top Events', 'valley-virtual-assistant'); ?></h2>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Event Type', 'valley-virtual-assistant'); ?></th>
                            <th><?php _e('Count', 'valley-virtual-assistant'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_events as $event) : ?>
                            <tr>
                                <td><?php echo esc_html($event->event_type); ?></td>
                                <td><?php echo number_format($event->count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}

<?php
/**
 * Valley Virtual Assistant - Diagnostics
 *
 * @package Valley_Virtual_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VVA_Diagnostics Class
 */
class VVA_Diagnostics {

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
        add_action('admin_menu', array($this, 'add_diagnostics_page'));
    }

    /**
     * Add diagnostics page to admin menu
     */
    public function add_diagnostics_page() {
        add_submenu_page(
            'valley-virtual-assistant',
            __('Diagnostics', 'valley-virtual-assistant'),
            __('Diagnostics', 'valley-virtual-assistant'),
            'manage_options',
            'vva-diagnostics',
            array($this, 'render_diagnostics_page')
        );
    }

    /**
     * Render diagnostics page
     */
    public function render_diagnostics_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Test product search
        $test_search = '';
        $search_results = array();
        if (isset($_POST['test_search'])) {
            $test_search = sanitize_text_field($_POST['search_query']);
            $wc = VVA_WooCommerce::instance();
            $search_results = $wc->search_products(array(
                's' => $test_search,
                'limit' => 10,
                'in_stock' => false, // Show ALL products to help diagnose stock issues
            ));
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Valley Virtual Assistant - Diagnostics', 'valley-virtual-assistant'); ?></h1>

            <div class="card">
                <h2><?php _e('System Status', 'valley-virtual-assistant'); ?></h2>
                <table class="widefat">
                    <tr>
                        <th><?php _e('WooCommerce Active', 'valley-virtual-assistant'); ?></th>
                        <td><?php echo class_exists('WooCommerce') ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('OpenAI API Key Set', 'valley-virtual-assistant'); ?></th>
                        <td><?php echo get_option('vva_openai_api_key') ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Widget Enabled', 'valley-virtual-assistant'); ?></th>
                        <td><?php echo get_option('vva_widget_enabled', true) ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Total Products', 'valley-virtual-assistant'); ?></th>
                        <td><?php echo wp_count_posts('product')->publish; ?></td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h2><?php _e('Test Product Search', 'valley-virtual-assistant'); ?></h2>
                <p><?php _e('Test if WooCommerce product search is working and returning products with images.', 'valley-virtual-assistant'); ?></p>

                <form method="post">
                    <input type="text" name="search_query" value="<?php echo esc_attr($test_search); ?>"
                           placeholder="<?php esc_attr_e('Enter search term (e.g., butt plug, vibrator)', 'valley-virtual-assistant'); ?>"
                           style="width: 300px;">
                    <button type="submit" name="test_search" class="button button-primary"><?php _e('Search Products', 'valley-virtual-assistant'); ?></button>
                </form>

                <?php if ($test_search): ?>
                    <hr>
                    <h3><?php printf(__('Search Results for: "%s"', 'valley-virtual-assistant'), esc_html($test_search)); ?></h3>

                    <?php if (!empty($search_results['products'])): ?>
                        <p><strong><?php printf(__('Found %d products', 'valley-virtual-assistant'), count($search_results['products'])); ?></strong></p>

                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('ID', 'valley-virtual-assistant'); ?></th>
                                    <th><?php _e('Image', 'valley-virtual-assistant'); ?></th>
                                    <th><?php _e('Name', 'valley-virtual-assistant'); ?></th>
                                    <th><?php _e('Price', 'valley-virtual-assistant'); ?></th>
                                    <th><?php _e('Stock', 'valley-virtual-assistant'); ?></th>
                                    <th><?php _e('Categories', 'valley-virtual-assistant'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($search_results['products'] as $product): ?>
                                    <tr>
                                        <td><?php echo esc_html($product['id']); ?></td>
                                        <td>
                                            <?php if ($product['image']): ?>
                                                <img src="<?php echo esc_url($product['image']); ?>" alt="" style="max-width: 50px; max-height: 50px;">
                                                <br><small><?php echo esc_html(basename($product['image'])); ?></small>
                                            <?php else: ?>
                                                ❌ No Image
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html($product['name']); ?></td>
                                        <td>$<?php echo esc_html($product['price']); ?></td>
                                        <td><?php echo $product['in_stock'] ? '✅ In Stock' : '❌ Out of Stock'; ?></td>
                                        <td>
                                            <?php
                                            if (!empty($product['categories'])) {
                                                echo esc_html(implode(', ', array_column($product['categories'], 'name')));
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-left: 4px solid #00a32a;">
                            <h4><?php _e('✅ Product Search is Working!', 'valley-virtual-assistant'); ?></h4>
                            <p><?php _e('The AI should receive these products and use their IDs. Check if images are loading above.', 'valley-virtual-assistant'); ?></p>
                        </div>

                    <?php else: ?>
                        <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-left: 4px solid #d63638;">
                            <h4><?php _e('❌ No Products Found', 'valley-virtual-assistant'); ?></h4>
                            <p><?php _e('WooCommerce search returned no results. This means:', 'valley-virtual-assistant'); ?></p>
                            <ul>
                                <li><?php _e('No products match your search term, or', 'valley-virtual-assistant'); ?></li>
                                <li><?php _e('Products are not published/in stock, or', 'valley-virtual-assistant'); ?></li>
                                <li><?php _e('WooCommerce search indexing needs to be rebuilt', 'valley-virtual-assistant'); ?></li>
                            </ul>
                            <p><strong><?php _e('Try searching for a different term or check your WooCommerce products.', 'valley-virtual-assistant'); ?></strong></p>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

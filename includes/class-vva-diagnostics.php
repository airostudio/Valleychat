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

        // Get a few random products for testing
        $random_products = array();
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 5,
            'orderby' => 'rand',
            'post_status' => 'publish',
        );
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $random_products[] = array(
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                    );
                }
            }
            wp_reset_postdata();
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

            <div class="card" style="background: #fff3cd; border-left: 4px solid #ffc107;">
                <h2><?php _e('🔍 Image Diagnostic Test', 'valley-virtual-assistant'); ?></h2>
                <p><strong><?php _e('Test image retrieval for random products from your store:', 'valley-virtual-assistant'); ?></strong></p>

                <?php if (!empty($random_products)): ?>
                    <table class="widefat" style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Image ID</th>
                                <th>Image URL Test</th>
                                <th>Image Preview</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($random_products as $prod_data) {
                                $product = wc_get_product($prod_data['id']);
                                if (!$product) continue;

                                $image_id = $product->get_image_id();
                                $image_url = '';

                                // Test our method
                                if ($image_id) {
                                    $image_url = wp_get_attachment_url($image_id);
                                    if (!$image_url) {
                                        $image_url = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail');
                                    }
                                    if (!$image_url) {
                                        $image_url = wp_get_attachment_image_url($image_id, 'medium');
                                    }
                                }

                                // Try gallery
                                if (!$image_url) {
                                    $gallery_ids = $product->get_gallery_image_ids();
                                    if (!empty($gallery_ids)) {
                                        $image_url = wp_get_attachment_url($gallery_ids[0]);
                                    }
                                }

                                // Placeholder
                                if (!$image_url) {
                                    $image_url = wc_placeholder_img_src('woocommerce_thumbnail');
                                }

                                ?>
                                <tr>
                                    <td><strong><?php echo $prod_data['id']; ?></strong></td>
                                    <td><?php echo esc_html($prod_data['name']); ?></td>
                                    <td><?php echo $image_id ? $image_id : '<span style="color:red;">❌ No Image ID</span>'; ?></td>
                                    <td style="font-size: 11px; word-break: break-all;">
                                        <?php if ($image_url): ?>
                                            <a href="<?php echo esc_url($image_url); ?>" target="_blank">✅ <?php echo esc_html($image_url); ?></a>
                                        <?php else: ?>
                                            <span style="color:red;">❌ No URL Generated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($image_url): ?>
                                            <img src="<?php echo esc_url($image_url); ?>" style="max-width: 80px; max-height: 80px; border: 1px solid #ddd;"
                                                 onerror="this.style.border='2px solid red'; this.alt='FAILED TO LOAD';">
                                        <?php else: ?>
                                            <span style="color:red;">No Image</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                    <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-left: 4px solid #2196f3;">
                        <h4>✅ What This Test Shows:</h4>
                        <ul>
                            <li>If images appear above: <strong>Image retrieval is working</strong> ✅</li>
                            <li>If images have red border or say "FAILED TO LOAD": <strong>Image files don't exist or URLs are broken</strong> ❌</li>
                            <li>If "No Image ID": <strong>Products don't have featured images set in WooCommerce</strong> ⚠️</li>
                        </ul>
                        <p><strong>Next Step:</strong> Compare these results with what you see in the chatbot. If images show here but not in chatbot, it's a JavaScript issue. If images don't show here, it's a WordPress/WooCommerce configuration issue.</p>
                    </div>
                <?php else: ?>
                    <p style="color: red;"><strong>❌ No products found in your store!</strong></p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2><?php _e('Test Product Search', 'valley-virtual-assistant'); ?></h2>
                <p><?php _e('Test if WooCommerce product search is working and returning products with images.', 'valley-virtual-assistant'); ?></p>

                <form method="post">
                    <input type="text" name="search_query" value="<?php echo esc_attr($test_search); ?>"
                           placeholder="<?php esc_attr_e('Enter search term', 'valley-virtual-assistant'); ?>"
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($search_results['products'] as $product): ?>
                                    <tr>
                                        <td><?php echo esc_html($product['id']); ?></td>
                                        <td>
                                            <?php if ($product['image']): ?>
                                                <img src="<?php echo esc_url($product['image']); ?>" alt="" style="max-width: 50px; max-height: 50px; border: 1px solid #ddd;"
                                                     onerror="this.style.border='2px solid red'; this.alt='FAILED';">
                                                <br><small style="font-size: 10px;"><?php echo esc_html(basename($product['image'])); ?></small>
                                            <?php else: ?>
                                                ❌ No Image URL
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html($product['name']); ?></td>
                                        <td>$<?php echo esc_html($product['price']); ?></td>
                                        <td><?php echo isset($product['in_stock']) && $product['in_stock'] ? '✅ In Stock' : '❌ Out of Stock'; ?></td>
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
                            <p><?php _e('WooCommerce search returned no results.', 'valley-virtual-assistant'); ?></p>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

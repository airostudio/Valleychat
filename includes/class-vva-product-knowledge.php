<?php
/**
 * Valley Virtual Assistant - Product Knowledge System
 *
 * @package Valley_Virtual_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VVA_Product_Knowledge Class
 */
class VVA_Product_Knowledge {

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
        // Hook to update product knowledge when products are saved
        add_action('woocommerce_update_product', array($this, 'update_product_knowledge'), 10, 1);
        add_action('woocommerce_new_product', array($this, 'update_product_knowledge'), 10, 1);
    }

    /**
     * Search products with AI-enhanced relevance
     *
     * @param array $params Search parameters
     * @return array Search results
     */
    public function search_products($params) {
        $query = isset($params['query']) ? $params['query'] : '';
        $category = isset($params['category']) ? $params['category'] : '';
        $limit = isset($params['limit']) ? (int) $params['limit'] : 10;

        // Use WooCommerce integration for basic search
        $wc = VVA_WooCommerce::instance();
        $results = $wc->search_products(array(
            's' => $query,
            'category' => $category,
            'limit' => $limit,
            'in_stock' => true,
        ));

        // Enhance with AI-based relevance scoring
        if (!empty($results['products'])) {
            $results['products'] = $this->rank_by_relevance($results['products'], $query);
        }

        return $results;
    }

    /**
     * Get product recommendations based on context
     *
     * @param array $context User context (browsing history, preferences, etc.)
     * @return array Recommended products
     */
    public function get_recommendations($context) {
        $recommendations = array();

        // Collaborative filtering based on browsing history
        if (!empty($context['viewed_products'])) {
            $recommendations = array_merge(
                $recommendations,
                $this->get_similar_products($context['viewed_products'])
            );
        }

        // Cart-based recommendations
        if (!empty($context['cart_items'])) {
            $recommendations = array_merge(
                $recommendations,
                $this->get_complementary_products($context['cart_items'])
            );
        }

        // Category-based recommendations
        if (!empty($context['preferred_categories'])) {
            $recommendations = array_merge(
                $recommendations,
                $this->get_category_recommendations($context['preferred_categories'])
            );
        }

        // Remove duplicates and limit results
        $recommendations = $this->deduplicate_products($recommendations);
        $recommendations = array_slice($recommendations, 0, 10);

        return $recommendations;
    }

    /**
     * Get similar products based on attributes and categories
     *
     * @param array $product_ids Array of product IDs
     * @return array Similar products
     */
    private function get_similar_products($product_ids) {
        $similar = array();
        $wc = VVA_WooCommerce::instance();

        foreach ($product_ids as $product_id) {
            $product_info = $wc->get_product_info($product_id);

            if (is_wp_error($product_info)) {
                continue;
            }

            // Find products in same categories
            if (!empty($product_info['categories'])) {
                foreach ($product_info['categories'] as $category) {
                    $category_products = $wc->search_products(array(
                        'category' => $category['slug'],
                        'limit' => 5,
                        'in_stock' => true,
                    ));

                    if (!empty($category_products['products'])) {
                        $similar = array_merge($similar, $category_products['products']);
                    }
                }
            }
        }

        return $similar;
    }

    /**
     * Get complementary products (often bought together)
     *
     * @param array $cart_items Cart items
     * @return array Complementary products
     */
    private function get_complementary_products($cart_items) {
        global $wpdb;

        $complementary = array();
        $product_ids = wp_list_pluck($cart_items, 'product_id');

        // Find products frequently purchased together
        // This would query order items to find common combinations
        $table = $wpdb->prefix . 'woocommerce_order_items';

        foreach ($product_ids as $product_id) {
            // Simplified query - in production, this would be more sophisticated
            $query = $wpdb->prepare("
                SELECT oi2.order_item_id, oim.meta_value as product_id, COUNT(*) as frequency
                FROM {$wpdb->prefix}woocommerce_order_items oi1
                JOIN {$wpdb->prefix}woocommerce_order_items oi2 ON oi1.order_id = oi2.order_id
                JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi2.order_item_id = oim.order_item_id
                WHERE oi1.order_item_id IN (
                    SELECT order_item_id
                    FROM {$wpdb->prefix}woocommerce_order_itemmeta
                    WHERE meta_key = '_product_id' AND meta_value = %d
                )
                AND oim.meta_key = '_product_id'
                AND oim.meta_value != %d
                GROUP BY oim.meta_value
                ORDER BY frequency DESC
                LIMIT 5
            ", $product_id, $product_id);

            $results = $wpdb->get_results($query);

            foreach ($results as $result) {
                $wc = VVA_WooCommerce::instance();
                $product = $wc->get_product_info($result->product_id);
                if (!is_wp_error($product)) {
                    $complementary[] = $product;
                }
            }
        }

        return $complementary;
    }

    /**
     * Get recommendations from specific categories
     *
     * @param array $categories Category slugs
     * @return array Products
     */
    private function get_category_recommendations($categories) {
        $products = array();
        $wc = VVA_WooCommerce::instance();

        foreach ($categories as $category) {
            $results = $wc->search_products(array(
                'category' => $category,
                'limit' => 5,
                'orderby' => 'popularity',
            ));

            if (!empty($results['products'])) {
                $products = array_merge($products, $results['products']);
            }
        }

        return $products;
    }

    /**
     * Rank products by relevance to search query
     *
     * @param array $products Products to rank
     * @param string $query Search query
     * @return array Ranked products
     */
    private function rank_by_relevance($products, $query) {
        if (empty($query)) {
            return $products;
        }

        $query_terms = explode(' ', strtolower($query));

        foreach ($products as &$product) {
            $score = 0;
            $text = strtolower($product['name'] . ' ' . $product['short_description']);

            // Score based on term matches
            foreach ($query_terms as $term) {
                $term = trim($term);
                if (empty($term)) {
                    continue;
                }

                // Exact phrase match in title
                if (strpos(strtolower($product['name']), $term) !== false) {
                    $score += 10;
                }

                // Match in description
                if (strpos($text, $term) !== false) {
                    $score += 5;
                }

                // Category match
                if (!empty($product['categories'])) {
                    foreach ($product['categories'] as $category) {
                        if (strpos(strtolower($category['name']), $term) !== false) {
                            $score += 3;
                        }
                    }
                }
            }

            // Boost for on-sale products
            if (!empty($product['on_sale'])) {
                $score += 2;
            }

            // Boost for in-stock products
            if (!empty($product['stock_status']) && $product['stock_status'] === 'instock') {
                $score += 1;
            }

            $product['relevance_score'] = $score;
        }

        // Sort by relevance score
        usort($products, function($a, $b) {
            return $b['relevance_score'] - $a['relevance_score'];
        });

        return $products;
    }

    /**
     * Remove duplicate products
     *
     * @param array $products Products array
     * @return array Deduplicated products
     */
    private function deduplicate_products($products) {
        $seen = array();
        $unique = array();

        foreach ($products as $product) {
            $id = isset($product['id']) ? $product['id'] : (isset($product['product_id']) ? $product['product_id'] : null);

            if ($id && !in_array($id, $seen)) {
                $seen[] = $id;
                $unique[] = $product;
            }
        }

        return $unique;
    }

    /**
     * Update product knowledge when product is saved
     *
     * @param int $product_id Product ID
     */
    public function update_product_knowledge($product_id) {
        // This could be used to build a vector database or search index
        // For now, we rely on WooCommerce's built-in search

        // Future enhancement: Index product data for semantic search
        do_action('vva_product_knowledge_updated', $product_id);
    }

    /**
     * Get product categories hierarchy
     *
     * @return array Category tree
     */
    public function get_category_tree() {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => 0,
        ));

        $tree = array();
        foreach ($categories as $category) {
            $tree[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count,
                'children' => $this->get_category_children($category->term_id),
            );
        }

        return $tree;
    }

    /**
     * Get category children
     *
     * @param int $parent_id Parent category ID
     * @return array Child categories
     */
    private function get_category_children($parent_id) {
        $children = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => $parent_id,
        ));

        $tree = array();
        foreach ($children as $child) {
            $tree[] = array(
                'id' => $child->term_id,
                'name' => $child->name,
                'slug' => $child->slug,
                'count' => $child->count,
            );
        }

        return $tree;
    }

    /**
     * Get popular products
     *
     * @param int $limit Number of products
     * @return array Popular products
     */
    public function get_popular_products($limit = 10) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'meta_key' => 'total_sales',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
        );

        $products = array();
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $products[] = array(
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'price' => $product->get_price(),
                        'url' => $product->get_permalink(),
                        'sales' => get_post_meta($product->get_id(), 'total_sales', true),
                    );
                }
            }
            wp_reset_postdata();
        }

        return $products;
    }

    /**
     * Get new arrivals
     *
     * @param int $limit Number of products
     * @return array New products
     */
    public function get_new_arrivals($limit = 10) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $products = array();
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $products[] = array(
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'price' => $product->get_price(),
                        'url' => $product->get_permalink(),
                        'date_created' => get_the_date('Y-m-d'),
                    );
                }
            }
            wp_reset_postdata();
        }

        return $products;
    }
}

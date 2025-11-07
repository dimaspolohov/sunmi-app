<?php
/**
 * Main WordPress core class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\REST\Product;

use Onepix\PluginTemplate\REST\Auth\Main as Auth;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Lieferchef_Table_Order;
use WP_Query;
use WC_Order;
use WC_Order_Item_Meta;
use WC_Product_Query;


defined( 'ABSPATH' ) || exit();

/**
 * Main WordPress core class
 *
 * @package Onepix\PluginTemplate
 */
class Main {
    /**
     * Main constructor.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }


    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            'custom/v1',
            '/products',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_products_request' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );
        register_rest_route(
            'custom/v1',
            '/categories',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_categories_request' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );
        register_rest_route(
            'custom/v1',
            '/product/(?P<product_id>\d+)/stock',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_stock_request' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );
        register_rest_route(
            'custom/v1',
            '/product/(?P<product_id>\d+)/pin',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_pin_request' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );
    }


    /**
     * Handle pin request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_pin_request(WP_REST_Request $request)
    {
        $product_id = intval($request->get_param( 'product_id' ));

        $product = wc_get_product( $product_id );

        $pinned =  self::is_pinned($product);

        if($pinned){
            $pinned = 'false';
        }else{
            $pinned = 'true';
        }

        $product->update_meta_data('_pinned', $pinned);
        $product->save();

        // Clear products transient cache after pin toggle
        self::clear_products_cache();

        return new WP_REST_Response(
            array(
                'product' => self::get_product($product),
            ),
            200
        );
    }


    /**
     * Handle stock request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_stock_request(WP_REST_Request $request)
    {
        $product_id = intval($request->get_param( 'product_id' ));
        $is_in_stock = boolval($request->get_param( 'is_in_stock' ));


        if ( !$is_in_stock ) {
            wp_update_post( [ 'ID' => $product_id, 'post_status' => 'private' ] );
        } else {
            wp_update_post( [ 'ID' => $product_id, 'post_status' => 'publish' ] );
        }

        $_product = wc_get_product( $product_id );

        // Clear products transient cache after stock status update
        self::clear_products_cache();

        return new WP_REST_Response(
            array(
                'product' => self::get_product($_product),
            ),
            200
        );
    }


    /**
     * Handle categories request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_categories_request(WP_REST_Request $request)
    {
        $categories_query = get_terms( ['taxonomy' => 'product_cat'] );

        $categories = [];

        foreach ($categories_query as $category){
            $categories[] = [
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
            ];
        }

        return new WP_REST_Response(
            array(
                'categories' => $categories,
            ),
            200
        );
    }

    /**
     * Handle products request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_products_request(WP_REST_Request $request)
    {
        $term_id      = intval($request->get_param('category_id'));
        $stock_status = $request->get_param('stock_status');
        $only_pinned  = boolval($request->get_param('only_pinned'));
        $search       = $request->get_param('search');

        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'any',
        );

        if (!empty($search)) {
            $args['s'] = $search;
        }

        if (!empty($term_id)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $term_id,
            );
        }

        if (!empty($stock_status)) {
            if($stock_status === 'outofstock'){
                $args['post_status'] = 'private';
            }else{
                $args['post_status'] = 'publish';
            }
        }

        if (!empty($only_pinned)) {
            $args['meta_query'][] = array(
                'key'   => '_pinned',
                'value' => 'true',
            );
        }

        $query    = new WP_Query($args);
        $products = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $products[] = self::get_product($product);
                }
            }
            wp_reset_postdata();
        }

        return new WP_REST_Response(
            array(
                'products' => $products,
            ),
            200
        );
    }

    public static function get_product($product): array
    {
        $terms = wc_get_product_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
        return [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'sku' => $product->get_sku(),
            'categories_ids' => $terms,
            'categories_names' => self::get_category_names($product),
            'pinned' => self::is_pinned($product),
            'is_in_stock' => get_post_status( $product->get_id() ) == 'publish',
        ];
    }

    public static function get_category_names($product): array
    {
        $category_ids = wc_get_product_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );

        $category_names = array();

        if ( ! empty( $category_ids ) && is_array( $category_ids ) ) {
            foreach ( $category_ids as $cat_id ) {
                $term = get_term( $cat_id, 'product_cat' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $category_names[] = $term->name;
                }
            }
        }

        return $category_names;
    }

    public static function is_pinned($product): bool
    {
        $pinned = $product->get_meta('_pinned');

        if (empty($pinned)){
            return false;
        }

        if($pinned !== 'true'){
            return false;
        }

        return true;
    }

    /**
     * Clear all transients related to products listing cache.
     * Matches keys like 'products_*' generated in handle_products_request.
     *
     * @return void
     */
    public static function clear_products_cache(): void
    {
        global $wpdb;

        $like   = $wpdb->esc_like('_transient_products_') . '%';
        $sql    = $wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like);
        $names  = $wpdb->get_col($sql);

        if (!empty($names)) {
            foreach ($names as $option_name) {
                // Convert option_name like '_transient_products_xxx' to transient key 'products_xxx'
                $transient_key = substr($option_name, strlen('_transient_'));
                delete_transient($transient_key);
            }
        }
    }
}

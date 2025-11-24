<?php
/**
 * Main WordPress core class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\REST\Shop;

use Onepix\PluginTemplate\REST\Auth\Main as Auth;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Lieferchef_Table_Order;
use WP_Query;
use WC_Order;
use WC_Order_Item_Meta;


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
        add_action( 'lieferchef_daily_report_print', array( $this, 'daily_report' ), 10, 2 );
    }

    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            'custom/v1',
            '/shop/status',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_shop_status' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );
        register_rest_route(
            'custom/v1',
            '/shop/settings',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_shop_settings' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );
        register_rest_route(
            'custom/v1',
            '/print/template',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_shop_template' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );
        register_rest_route(
            'custom/v1',
                '/print/report',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_shop_report' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );
        register_rest_route(
            'custom/v1',
            '/shop/status',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_change_shop_status' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );
        register_rest_route(
            'custom/v1',
            '/shop/settings',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_change_shop_settings' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );
    }

    /**
     * Handle change shop status request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_change_shop_status(WP_REST_Request $request)
    {
        $options = get_option( 'lieferchef_global_settings' );

        $restaurant = $request->get_param( 'restaurant' );
        if($restaurant == 'close'){
            $delivery = $restaurant;
            $pickup = $restaurant;
        }else{
            $delivery = $request->get_param( 'delivery' );
            $pickup = $request->get_param( 'pickup' );
        }

        $prepare_time = $request->get_param( 'prepare_time' );
        $asap = $request->get_param( 'asap' );

        $options['lieferchef_open_close_id'] = $restaurant;
        $options['lieferchef_delivery_open_close_id'] = $delivery;
        $options['lieferchef_pickup_open_close_id'] = $pickup;
        $options['delivery_preparation_time'] = $prepare_time;
        $options['disable_asap_temp'] = $asap;

        update_option('lieferchef_global_settings', $options);

        return new WP_REST_Response(
            self::get_options($options),
            200
        );
    }

    /**
     * Handle change shop status request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_change_shop_settings(WP_REST_Request $request)
    {
        $options = get_option( 'lieferchef_global_settings' );
        $sunmi_options = get_option( 'sunmi-settings' );
        if ( ! is_array( $sunmi_options ) ) {
            $sunmi_options = array();
        }

        $restaurant = $request->get_param( 'restaurant' );
        if($restaurant == 'close'){
            $delivery = $restaurant;
            $pickup = $restaurant;
        }else{
            $delivery = $request->get_param( 'delivery' );
            $pickup = $request->get_param( 'pickup' );
        }

        $prepare_time = $request->get_param( 'default_preparation' );

        // New font size settings
        $font_size_address = $request->get_param( 'font_size_address' );
        if ( $font_size_address === null ) {
            $font_size_address = $request->get_param( 'print_data_font_size_address' );
        }
        $font_size_products = $request->get_param( 'font_size_products' );
        if ( $font_size_products === null ) {
            $font_size_products = $request->get_param( 'print_data_font_size_products' );
        }
        $font_size_payment = $request->get_param( 'font_size_payment' );
        if ( $font_size_payment === null ) {
            $font_size_payment = $request->get_param( 'print_data_font_size_payment' );
        }

        $options['lieferchef_open_close_id'] = $restaurant;
        $options['lieferchef_delivery_open_close_id'] = $delivery;
        $options['lieferchef_pickup_open_close_id'] = $pickup;
        $options['default_preparationtime'] = $prepare_time;

        // Persist sunmi print settings if provided
        if ( $font_size_address !== null ) {
            $sunmi_options['print_data_font_size_address'] = $font_size_address;
        }
        if ( $font_size_products !== null ) {
            $sunmi_options['print_data_font_size_products'] = $font_size_products;
        }
        if ( $font_size_payment !== null ) {
            $sunmi_options['print_data_font_size_payment'] = $font_size_payment;
        }
        update_option( 'sunmi-settings', $sunmi_options );

        update_option('lieferchef_global_settings', $options);

        return new WP_REST_Response(
            self::get_settings($options),
            200
        );
    }

    /**
     * Handle shop status request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_shop_status(WP_REST_Request $request)
    {
        return new WP_REST_Response(
            self::get_options(),
            200
        );
    }

    /**
     * Handle shop status request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_shop_settings(WP_REST_Request $request)
    {
        return new WP_REST_Response(
            self::get_settings(),
            200
        );
    }


    public function daily_report($report, $selected_date)
    {
        update_option('sunmi_print_report', $report);
    }

    /**
     * Get shop report for print.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_REST_Response Response or error.
     */
    public static function handle_shop_report(WP_REST_Request $request)
    {
        $sunmi_report = get_option( 'sunmi_print_report' );
        if(!is_null($sunmi_report)){
            update_option('sunmi_print_report', null);
        }

        return new WP_REST_Response(
            [
                'report' => $sunmi_report,
            ],
            200
        );
    }


    /**
     * Handle shop template for print.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_shop_template(WP_REST_Request $request)
    {
        $sunmi_options = get_option( 'sunmi-settings' );
        $logo = $sunmi_options['print_data_logo'] ?? null;
        if ( ! empty( $logo ) ) {
            $logo = set_url_scheme( $logo, 'https' );
        }

        $options = get_option( 'lieferchef_global_settings' );

        return new WP_REST_Response(
            [
                'footer' => $sunmi_options['print_data_footer'] ?? 'Enjoy your meal!',
                'logo' => $logo,
                'phone' => $options['lieferchef_phone'],
                'address' => $options['lieferchef_address'] . ', ' . $options['lieferchef_zip'] . ', ' . $options['lieferchef_city'],
                'vat' => $options['lieferchef_vat'],
                'company_name' => $options['lieferchef_company'],
            ],
            200
        );
    }

    public static function get_options(array $options = [])
    {
        if(empty($options)){
            $options = get_option( 'lieferchef_global_settings' );
        }

        return array(
            'restaurant' => $options['lieferchef_open_close_id'],
            'delivery' => $options['lieferchef_delivery_open_close_id'],
            'pickup' => $options['lieferchef_pickup_open_close_id'],
            'prepare_time' => $options['delivery_preparation_time'],
            'asap' => $options['disable_asap_temp'],
        );
    }

    public static function get_settings(array $options = [])
    {
        if(empty($options)){
            $options = get_option( 'lieferchef_global_settings' );
        }

        $sunmi_options = get_option( 'sunmi-settings' );
        return array(
            'restaurant' => $options['lieferchef_open_close_id'],
            'delivery' => $options['lieferchef_delivery_open_close_id'],
            'pickup' => $options['lieferchef_pickup_open_close_id'],
            'default_preparation' => $options['default_preparationtime'],
            'font_size_address' => $sunmi_options['print_data_font_size_address'],
            'font_size_products' => $sunmi_options['print_data_font_size_products'],
            'font_size_payment' => $sunmi_options['print_data_font_size_payment'],
        );
    }

}

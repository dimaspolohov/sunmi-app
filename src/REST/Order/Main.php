<?php
/**
 * Main WordPress core class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\REST\Order;

use DateTime;
use Exception;
use Onepix\PluginTemplate\REST\Auth\Main as Auth;
use Onepix\PluginTemplate\Pushy\Main as PushyConnector;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Lieferchef_Table_Order;
use WP_Query;
use WC_Order;
use WC_Order_Item_Meta;
use Email_Class;
use WP_User_Query;

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
        add_action( 'woocommerce_order_status_processing', array( $this, 'push_new_order' ), 10, 1 );
    }

    /**
     * @throws Exception
     */
    public function push_new_order($order_id)
    {
        $order = wc_get_order( $order_id );

        $data = [
            'notification' => ['title' => 'New order', 'body'=> 'New order №'.$order_id.' was received!'],
            'data' => self::get_order($order, true),
        ];

        PushyConnector::push($data);
    }

    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            'custom/v1',
            '/orders',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_orders_request' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );

        register_rest_route(
            'custom/v1',
            '/order/(?P<order_id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_order_request' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );

        register_rest_route(
            'custom/v1',
            '/order/(?P<order_id>\d+)/confirm',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_order_confirm' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );

        register_rest_route(
            'custom/v1',
            '/order/(?P<order_id>\d+)/delivery',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_order_delivery' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );

        register_rest_route(
            'custom/v1',
            '/order/(?P<order_id>\d+)/complete',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_order_complete' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );

        register_rest_route(
            'custom/v1',
            '/order/(?P<order_id>\d+)/reject',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_order_reject' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );

        register_rest_route(
            'custom/v1',
            '/order/(?P<order_id>\d+)/time',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_order_time_change' ),
                'permission_callback' => array( Auth::class, 'check_jwt_permission' ),
            ),
        );
    }

    /**
     * Handle order reject request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_order_time_change(WP_REST_Request $request)
    {
        $order_id = $request->get_param( 'order_id' );
        $delivery_time = $request->get_param( 'delivery_time' );

        if(empty($order_id)){
            return new WP_Error( 'error', 'Order ID must be provided', array( 'status' => 400 ) );
        }

        $time_updated = intval(get_post_meta( $order_id, 'time_updated', true ));

        $order = wc_get_order( $order_id );
        $order->update_meta_data( 'delivery_time', $delivery_time );
        $order->update_meta_data( 'time_updated', $time_updated + 1 );
        $order->save();

        Email_Class::send_adjusted_time_email($order);

        return new WP_REST_Response(
            array(
                'order' => self::get_order($order),
            ),
            200
        );
    }

    /**
     * Handle order reject request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_order_reject(WP_REST_Request $request)
    {
        $order_id = $request->get_param( 'order_id' );

        if(empty($order_id)){
            return new WP_Error( 'error', 'Order ID must be provided', array( 'status' => 400 ) );
        }

        $order = wc_get_order( $order_id );


        if(self::payment_method_type($order) == 'cash'){
            $order->update_status('cancelled', 'Order was canceled');
            $order->save();
        }else{
            wc_order_fully_refunded($order_id);
            $order = wc_get_order( $order_id );
        }

        return new WP_REST_Response(
            array(
                'order' => self::get_order($order),
            ),
            200
        );
    }

    public static function payment_method_type($order)
    {
        if($order->get_payment_method() == 'bacs' || $order->get_payment_method() == 'cheque' || $order->get_payment_method() == 'cod' || $order->get_payment_method() == 'cash'){
            return 'cash';
        }

        return 'card';
    }

    /**
     * Handle order complete request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_order_complete(WP_REST_Request $request)
    {
        $order_id = $request->get_param( 'order_id' );

        if(empty($order_id)){
            return new WP_Error( 'error', 'Order ID must be provided', array( 'status' => 400 ) );
        }

        $order = wc_get_order( $order_id );
        $order->update_meta_data( 'completed_status_time', current_time('H:i') );
        $_GET['action'] = 'woocommerce_mark_order_status';
        $order->update_status('completed', 'Order move to complete');
        $order->save();

        return new WP_REST_Response(
            array(
                'order' => self::get_order($order),
            ),
            200
        );
    }

    /**
     * Handle order move to delivery request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_order_delivery(WP_REST_Request $request)
    {
        $order_id = $request->get_param( 'order_id' );
        $order = wc_get_order( $order_id );
        $order->update_meta_data( 'delivery_status_time', current_time('H:i') );
        $order->update_status('in-delivery', 'Order move to delivery');
        $order->save();

        return new WP_REST_Response(
            array(
                'order' => self::get_order($order),
            ),
            200
        );
    }

    /**
     * Handle order confirm request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_order_confirm(WP_REST_Request $request)
    {
        $order_id = $request->get_param( 'order_id' );
        $old_delivery_time = get_post_meta( $order_id, 'delivery_time', true );
        $order = wc_get_order( $order_id );
        $order->update_meta_data( 'old_delivery_time', $old_delivery_time );
        $order->save();

        $delivery_time = $request->get_param( 'delivery_time' );
        $order->update_meta_data( 'delivery_time', $delivery_time );
        $order->update_meta_data( 'preparing_status_time', current_time('H:i') );
        $order->update_status('preparing', 'Order move to kitchen with delivery time ' . $delivery_time);
        $order->save();

        if ($order->get_meta('delivery_choose') == 'preorder') {
            do_action('lieferchef_preorder_confirmation', $order_id);
        } else {
            do_action('lieferchef_delivery_confirmation', $order_id);
        }

        return new WP_REST_Response(
            array(
                'order' => self::get_order($order),
            ),
            200
        );
    }

    /**
     * Handle order request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_order_request(WP_REST_Request $request)
    {
        $order_id = $request->get_param( 'order_id' );
        $order = wc_get_order( $order_id );

        return new WP_REST_Response(
            array(
                'order' => self::get_order($order),
            ),
            200
        );
    }


    /**
     * Handle orders request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_orders_request(WP_REST_Request $request)
    {
        $today = date('Y-m-d');

        $orders_created_today = wc_get_orders(array(
            'limit'        => -1,
            'orderby'      => 'date',
            'order'        => 'DESC',
            'status'       => ['wc-processing', 'wc-preparing', 'wc-in-delivery', 'wc-completed'],
            'date_created' => $today . ' 00:00:00...' . $today . ' 23:59:59',
        ));

        $orders_with_delivery_time = wc_get_orders(array(
            'limit'      => -1,
            'orderby'    => 'date',
            'order'      => 'DESC',
            'status'     => ['wc-processing', 'wc-preparing', 'wc-in-delivery', 'wc-completed'],
            'meta_query' => array(
                array(
                    'key'     => 'delivery_time',
                    'compare' => 'EXISTS',
                ),
            ),
        ));

        $delivery_orders_today = array_filter($orders_with_delivery_time, function($order) use ($today) {
            $delivery_raw = $order->get_meta('delivery_time');

            if (empty($delivery_raw)) return false;

            try {
                $delivery = new DateTime($delivery_raw); // автоматически учитывает временную зону в строке
                return $delivery->format('Y-m-d') === $today;
            } catch (Exception $e) {
                return false; // Если delivery_time невалиден
            }
        });

        $all_orders = array_merge($orders_created_today, $delivery_orders_today);
        $unique_orders = [];
        foreach ($all_orders as $order) {
            $unique_orders[$order->get_id()] = $order;
        }

        $filtered_orders = array_values($unique_orders);

        $orders = array();

        foreach ($filtered_orders as $order){
           if($order instanceof WC_Order) {
               $status = str_replace('wc-', '', $order->get_status());
               $orders[$status][] = [
                   'id' => $order->get_id(),
                   'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                   'items_count' => count($order->get_items()),
                   'total' => $order->get_total(),
                   'status' => $order->get_status(),
                   'payment_method_type' => self::payment_method_type($order),
                   'delivery_type' => Lieferchef_Table_Order::get_delivery_type($order),
                   'delivery_time' => get_post_meta( $order->get_id(), 'delivery_time', true ),
                   'approximate_time' => get_post_meta( $order->get_id(), 'delivery_time', true ),
                   'created_time' => $order->get_date_created(),
               ];
           }
        }

        return new WP_REST_Response(
            array(
                'orders' => $orders,
            ),
            200
        );
    }

    public static function get_order(WC_Order $order, $encode_array = false): array
    {
        $items = [];
        $tax_items_labels   = array();

        foreach ( $order->get_items('tax') as $tax_item ) {
            // Set the tax labels by rate ID in an array
            $tax_items_labels[$tax_item->get_rate_id()] = $tax_item->get_label();
        }

        foreach ( $order->get_items() as $item_id => $item ) {
            $total = $item->get_total();

            $taxes = $item->get_taxes();
            // Loop through taxes array to get the right label
            foreach( $taxes['subtotal'] as $rate_id => $tax ){
                $tax_label = $tax_items_labels[$rate_id];
            }

            $items[$item_id] = [
                'name' => $item->get_name(),
                'sku' => $item->get_product()->get_sku(),
                'categories_ids' => wc_get_product_terms( $item->get_product()->get_id(), 'product_cat', array( 'fields' => 'ids' ) ),
                'categories_names' => \Onepix\PluginTemplate\REST\Product\Main::get_category_names($item->get_product()),
                'quantity' => $item->get_quantity(),
                'total' => floatval($total),
                'total_with_addons' => floatval($total),
                'tax' => floatval($item->get_subtotal_tax()),
                'tax_label' => $tax_label,
                'total_with_tax' => round(floatval($total) + floatval($item->get_subtotal_tax()), 2),
                'addons' => [],
            ];

            $meta = new WC_Order_Item_Meta($item, $item->get_product());

            foreach($meta->get_formatted('_') as $meta_key => $formatted_meta) {
                if(is_array($formatted_meta['value'])){
                    $value = $formatted_meta['value'];
                    $price = ($value['price'] * $value['quantity']) * $item->get_quantity();
                    $items[$item_id]['addons'][] = array(
                        'name'   => $value['group'] . ': ' . $value['value'],
                        'price' => $value['prefix'] . $price,
                        'quantity' => $value['quantity'],
                    );
                    
                    if($value['prefix'] == '-') {
                        $total += $price;
                    }else{
                        $total -= $price;
                    }

                    $items[$item_id]['total'] = round($total, 2);
                }
            }
        }

        // Calculate totals by tax type (Reduced vs Standard)
        $tax_data = [];
        $tax_totals = [
            'reduced' => [
                'total_without_tax' => 0,
                'tax_amount' => 0,
                'total_with_tax' => 0,
                'items_count' => 0
            ],
            'standard' => [
                'total_without_tax' => 0,
                'tax_amount' => 0,
                'total_with_tax' => 0,
                'items_count' => 0
            ]
        ];

        // Group items by tax type and calculate totals
        foreach ($order->get_items() as $item_id => $item) {
            $item_total = $item->get_total();
            $item_tax = $item->get_subtotal_tax();
            $item_total_with_tax = $item_total + $item_tax;
            
            // Get tax label to determine tax type
            $taxes = $item->get_taxes();
            $tax_label = '';
            foreach($taxes['subtotal'] as $rate_id => $tax) {
                $tax_label = $tax_items_labels[$rate_id];
                break; // Get the first tax label
            }
            
            // Determine tax type based on label
            $tax_type = 'standard'; // Default to standard
            if (stripos($tax_label, 'reduced') !== false || stripos($tax_label, 'ermäßigt') !== false) {
                $tax_type = 'reduced';
            }
            
            // Add to appropriate tax type totals
            $tax_totals[$tax_type]['total_without_tax'] += $item_total;
            $tax_totals[$tax_type]['tax_amount'] += $item_tax;
            $tax_totals[$tax_type]['total_with_tax'] += $item_total_with_tax;
            $tax_totals[$tax_type]['items_count'] += $item->get_quantity();
        }

        // Format tax data for response
        foreach ($tax_totals as $type => $totals) {
            if ($totals['items_count'] > 0) { // Only include tax types that have items
                $tax_data[] = [
                    'type' => $type,
                    'name' => ucfirst($type) . ' Tax',
                    'total_without_tax' => round($totals['total_without_tax'], 2),
                    'tax_amount' => round($totals['tax_amount'], 2),
                    'total_with_tax' => round($totals['total_with_tax'], 2),
                    'items_count' => $totals['items_count']
                ];
            }
        }

        foreach ($order->get_items('fee') as $item_id => $item_fee) {
            $items[$item_id] = [
                'name' => $item_fee->get_name(),
                'quantity' => $item_fee->get_quantity(),
                'total' => floatval($item_fee->get_total()),
                'total_with_addons' => floatval($item_fee->get_total()),
                'addons' => [],
            ];
        }

        $options = get_option( 'lieferchef_global_settings' );

        $approximate_time = new DateTime(get_post_meta( $order->get_id(), 'approximate_time', true ));
        $approximate_time_min = $approximate_time->diff($order->get_date_created())->i;

        $status_times = [
            'new' => $order->get_date_created()->date('H:i'),
            'preparing' => get_post_meta( $order->get_id(), 'preparing_status_time', true ),
            'delivery' => get_post_meta( $order->get_id(), 'delivery_status_time', true ),
            'completed' => get_post_meta( $order->get_id(), 'completed_status_time', true ),
        ];

        $order_address = $order->get_address();
        $address = $order_address['address_1'] . $order_address['address_2'] . ', ' . $order_address['postcode'] . ', ' . $order_address['city'];
        $map_url = "https://www.google.com/maps?f=d&saddr=&daddr=".str_replace(" ", "+", $address)."&dirflg=";

        return [
            'id' => $order->get_id(),
            'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'phone' => $order->get_billing_phone(),
            'delivery_type' => Lieferchef_Table_Order::get_delivery_type($order),
            'old_delivery_time' => get_post_meta( $order->get_id(), 'old_delivery_time', true ),
            'delivery_time' => get_post_meta( $order->get_id(), 'delivery_time', true ),
            'date_created' => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
            'time_updated_count' => get_post_meta( $order->get_id(), 'time_updated', true ),
            'approximate_time' => get_post_meta( $order->get_id(), 'approximate_time', true ),
            'approximate_time_min' => $approximate_time_min,
            'preparation_time' => $options['delivery_preparation_time'],
            'items_count' => count($order->get_items()),
            'payment_method' => $order->get_payment_method_title(),
            'payment_method_type' => self::payment_method_type($order),
            'payment_method_id' => $order->get_payment_method(),
            'address' => $encode_array ? json_encode($order->get_address()) : $order->get_address(),
            'map_link' => $map_url,
            'status' => $order->get_status(),
            'note' => $order->get_customer_note(),
            'subtotal' => $order->get_subtotal(),
            'discount' => floatval($order->get_total_discount()),
            'shipping' => floatval($order->get_shipping_total()),
            'total' => $order->get_total(),
            'tax_data' => $tax_data,
            'status_times' => $encode_array ? json_encode($status_times) : $status_times,
            'items' => $encode_array ? json_encode($items) : $items,
        ];
    }
}

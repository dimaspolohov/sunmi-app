<?php

namespace Onepix\PluginTemplate\REST\Auth;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\SignatureInvalidException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_Session_Tokens;

/**
 * Registration handling for REST API.
 */
class Main {

    /**
     * Constructor: Init registration route.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        add_action( 'init', array( $this, 'start_session' ), 1 );
        add_action( 'wp_login', array( $this, 'set_user_session' ), 10, 2 );
        add_action( 'wp_logout', array( $this, 'unset_user_session' ) );
    }

    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            'custom/v1',
            '/logout',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_logout_request' ),
                'permission_callback' => array( self::class, 'check_jwt_permission' ),
            ),
        );
        register_rest_route(
            'custom/v1',
            '/get-token',
            array(
                'methods'  => 'POST',
                'callback' => array( $this, 'get_token' ),
            )
        );
        register_rest_route(
            'custom/v1',
            '/save-pushy-token',
            array(
                'methods'  => 'POST',
                'callback' => array( $this, 'save_pushy_token' ),
                'permission_callback' => array( self::class, 'check_jwt_permission' ),
            )
        );
        register_rest_route(
            'custom/v1',
            '/qr-login/exchange',
            array(
                'methods'  => 'POST',
                'callback' => array( $this, 'exchange_qr_code' ),
            )
        );
    }

    /**
     * Save Pushy token
     *
     * @param WP_REST_Request $request REST request data.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function save_pushy_token( WP_REST_Request $request ) {
        $params = $request->get_params();
        $user_id = !empty($params['params']['user_id']) ? $request['params']['user_id'] : $params['user_id'];
        $token = !empty($params['params']['token']) ? $params['params']['token'] : $params['token'];
        $old_token = !empty($params['params']['old_token']) ? $params['params']['old_token'] : $params['old_token'];

        error_log('Save Pushy token request params - ' . print_r($params, true));

        if(empty($token)){
            return new WP_Error( 'error', 'Please provide token', array( 'status' => 401 ) );
        }

        $tokens = get_user_meta( $user_id, 'pushy_tokens', true );

        error_log('User Id to save tokens - ' . $user_id);


        if(empty($tokens)){
            $tokens = [$token];
        }elseif(!in_array($token, $tokens)){
            $tokens[] = $token;
        }

        if($old_token !== $token){
            if(in_array($old_token, $tokens)){
                $index = array_search($old_token, $tokens);
                if ($index !== false) {
                    unset($tokens[$index]);
                    $tokens = array_values($tokens);
                }
            }
        }

        error_log('Exist Pushy tokens - ' . print_r($tokens, true));

        update_user_meta( $user_id, 'pushy_tokens', $tokens );

        return new WP_REST_Response(
            array(
                'tokens' => $tokens,
            ),
            200
        );
    }

    /**
     * Get token
     *
     * @param WP_REST_Request $request REST request data.
     * @return array|WP_Error
     */
    public function get_token( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( empty( $params ) ) {
            return new WP_Error( 'error', 'No request data provided.', array( 'status' => 401 ) );
        }

        $email = $params['email'] ?? null;
        $password = $params['password'] ?? null;

        if ( empty( $email ) || empty( $password ) ) {
            return new WP_Error( 'error', 'Missing email or password.', array( 'status' => 401 ) );
        }

        $user = get_user_by( 'email', $email );

        if ( ! $user ) {
            return new WP_Error( 'error', 'User not found.', array( 'status' => 401 ) );
        }

        if ( ! wp_check_password( $password, $user->data->user_pass, $user->ID ) ){
            return new WP_Error( 'error', 'User password mismatch', array( 'status' => 401 ) );
        }

        return self::create_jwt_token( $user->ID );
    }

    /**
     * Start session.
     *
     * @return void
     */
    public function start_session() {
        if ( ! session_id() ) {
            session_start();
        }
    }

    /**
     * Set user session.
     *
     * @param string $user_login - user login name.
     * @param object $user - user object.
     * @return void - set user session.
     */
    public function set_user_session( $user_login, $user ) {
        $_SESSION['user_id'] = $user->ID;
    }

    /**
     * Unset user session.
     *
     * @return void
     */
    public function unset_user_session() {
        unset( $_SESSION['user_id'] );
    }

    /**
     * Handle logout request.
     *
     * @param WP_REST_Request $request REST request data.
     * @return WP_Error|WP_REST_Response Response or error.
     */
    public static function handle_logout_request( WP_REST_Request $request ) {
        $user_id = $request->get_param( 'user_id' );

        if ( ! empty( $user_id ) ) {
            $user = get_user_by( 'ID', $user_id );

            if ( ! $user ) {
                return new WP_Error( 'error', 'User not found.', array( 'status' => 401 ) );
            }

            if ( class_exists( 'WP_Session_Tokens' ) ) {
                $manager = WP_Session_Tokens::get_instance( $user_id );
                $manager->destroy_all();
            }

            wp_clear_auth_cookie();
            wp_set_current_user( 0 );

            do_action( 'wp_logout', $user_id );

            setcookie( 'auth_token', '', time() - 3600, '/', '', false, true );

            return new WP_REST_Response(
                array(
                    'message' => "User with ID {$user_id} logged out successfully.",
                ),
                200
            );
        }

        return new WP_Error( 'error', 'User ID is required.', array( 'status' => 401 ) );
    }

    /**
     * Exchange one-time QR code for JWT token.
     *
     * @param WP_REST_Request $request REST request data.
     * @return array|WP_Error
     */
    public function exchange_qr_code( WP_REST_Request $request ) {
        $code = $request->get_param( 'code' );

        if ( empty( $code ) ) {
            return new WP_Error( 'error', 'Missing code.', array( 'status' => 401 ) );
        }

        $key     = 'qr_login_' . sanitize_text_field( $code );
        $user_id = get_transient( $key );

        if ( false === $user_id ) {
            return new WP_Error( 'error', 'Invalid or expired code.', array( 'status' => 401 ) );
        }

        delete_transient( $key );

        $user = get_user_by( 'ID', (int) $user_id );
        if ( ! $user ) {
            return new WP_Error( 'error', 'User not found.', array( 'status' => 401 ) );
        }

        return self::create_jwt_token( (int) $user_id );
    }

    /**
     * Create JWT token.
     *
     * @param int $user_id - User ID.
     * @return array
     */
    public static function create_jwt_token( int $user_id ): array {
        $token = array(
            'iss'  => get_site_url(),
            'iat'  => time(),
            //'exp'  => time() + 86400,
            'data' => array(
                'user' => array(
                    'id' => $user_id,
                ),
            ),
        );

        return array(
            'user_id'    => $user_id,
            'token'      => JWT::encode( $token, JWT_AUTH_SECRET_KEY, 'HS256' ),
            'issued_at'  => date( 'Y-m-d H:i:s', $token['iat'] ),
            //'expires_at' => date( 'Y-m-d H:i:s', $token['exp'] ),
        );
    }

    /**
     * Check JWT permission.
     *
     * @param WP_REST_Request $data - REST request data.
     * @return true|WP_Error
     */
    public static function check_jwt_permission( WP_REST_Request $data = null ) {
        $header = getallheaders();
        $header = array_change_key_case( $header, CASE_LOWER );

        if ( empty( $header['authorization'] ) ) {
            return new WP_Error( 'jwt_auth_no_auth_header', 'Authorization header not found.', array( 'status' => 401 ) );
        }

        $token = str_replace( 'Bearer ', '', $header['authorization'] );

        if ( is_null( $token ) ) {
            $headers = getallheaders();

            if ( ! isset( $headers['authorization'] ) ) {
                return new WP_Error( 'jwt_auth_no_auth_header', 'Authorization header not found.', array( 'status' => 401 ) );
            }

            $token = str_replace( 'Bearer ', '', $headers['authorization'] );

            if ( empty( $token ) ) {
                return new WP_Error( 'jwt_auth_no_token', 'JWT token not found.', array( 'status' => 401 ) );
            }
        }

        try {
            $decoded = JWT::decode( $token, new Key( JWT_AUTH_SECRET_KEY, 'HS256' ) );
        } catch ( ExpiredException $e ) {
            return new WP_Error( 'jwt_auth_token_expired', 'JWT token has expired.', array( 'status' => 401 ) );
        } catch ( BeforeValidException $e ) {
            return new WP_Error( 'jwt_auth_token_not_yet_valid', 'JWT token is not yet valid.', array( 'status' => 401 ) );
        } catch ( SignatureInvalidException $e ) {
            return new WP_Error( 'jwt_auth_invalid_token', 'Invalid JWT token signature.', array( 'status' => 401 ) );
        } catch ( Exception $e ) {
            return new WP_Error( 'jwt_auth_invalid_token', 'Invalid JWT token.', array( 'status' => 401 ) );
        }

        if ( ! isset( $decoded->data->user->id ) ) {
            return new WP_Error( 'jwt_auth_invalid_token', 'Invalid JWT token.', array( 'status' => 401 ) );
        }

        return true;
    }
}

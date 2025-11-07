<?php
/**
 * Pushy push notifications class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\Pushy;

use WP_User_Query;

defined( 'ABSPATH' ) || exit();

/**
 * Pushy push notifications class
 *
 * @package Onepix\PluginTemplate
 */
class Main {
	/**
	 * Pushy constructor.
	 */
	public function __construct() {

	}

    /**
     * Send push notification via Pushy
     *
     * @param array $data Push notification data
     * @return void
     */
    public static function push($data)
    {
        $args = array(
            'meta_query' => array(
                array(
                    'key'     => 'pushy_tokens',
                    'compare' => 'EXISTS'
                ),
            )
        );
        $user_query = new WP_User_Query( $args );

        $deviceTokens = [];

        if ( ! empty( $user_query->get_results() ) ) {
            foreach ( $user_query->get_results() as $user ) {
                $tokens = get_user_meta($user->ID, 'pushy_tokens', true);
                if(!empty($tokens)){
                    $deviceTokens[] = $tokens;
                }
            }
        }

        if(!empty($deviceTokens)){
            $tokensToSend = [];
            foreach ($deviceTokens[0] as $deviceToken){
                if(!is_null($deviceToken)){
                    $tokensToSend[] = $deviceToken;
                }
            }

            $tokensToSend = array_unique($tokensToSend);

            error_log('Pushy send tokens - ' . print_r($tokensToSend, true));
            
            try {
                self::sendPushNotification($data, $tokensToSend);
            } catch (\Exception $e) {
                error_log( 'Error to send message: ' . $e->getMessage() );
            }
        }
    }

    /**
     * Send push notification to Pushy API
     *
     * @param array $data Notification data
     * @param array $to Device tokens
     * @return void
     */
    private static function sendPushNotification($data, $to) {
        // Secret API Key
        $apiKey = 'fc55788083957d0e7167c4a718a07e44bf54758fc5e4bed66c7f5793bbea6e9b';

        // Prepare post data
        $post = array();
        
        // Set notification payload and recipients
        $post['to'] = $to;
        $post['data'] = isset($data['data']) ? $data['data'] : $data;
        
        // Add notification options if provided
        if (isset($data['notification'])) {
            $post['notification'] = $data['notification'];
        }

        // Set Content-Type header since we're sending JSON
        $headers = array(
            'Content-Type: application/json'
        );

        // Initialize curl handle
        $ch = curl_init();

        // Set URL to Pushy endpoint
        curl_setopt($ch, CURLOPT_URL, 'https://api.pushy.me/push?api_key=' . $apiKey);

        // Set request method to POST
        curl_setopt($ch, CURLOPT_POST, true);

        // Set our custom headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Get the response back as string instead of printing it
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Set post data as JSON
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post, JSON_UNESCAPED_UNICODE));

        // Actually send the push
        $result = curl_exec($ch);

        // Display errors
        if (curl_errno($ch)) {
            error_log('Pushy cURL error: ' . curl_error($ch));
        }

        // Close curl handle
        curl_close($ch);

        // Attempt to parse JSON response
        $response = @json_decode($result);

        error_log('Pushy send report data - ' . print_r($post, true));
        error_log('Pushy send report - ' . print_r($response, true));

        // Throw if JSON error returned
        if (isset($response) && isset($response->error)) {
            throw new \Exception('Pushy API returned an error: ' . $response->error);
        }
    }
}


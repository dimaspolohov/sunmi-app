<?php
/**
 * Example ajax class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\Ajax;

defined( 'ABSPATH' ) || exit();

/**
 * Example ajax class
 *
 * @package Onepix\PluginTemplate
 */
class Example extends Base {
	/**
	 * Prefix for actions
	 *
	 * @var string
	 */
	const PREFIX = 'example';

	/**
	 * Ajax for wc api (registration with prefix)
	 *
	 * @var array
	 */
	const ACTIONS = array(
		'test',
	);

	/**
	 * Get order data
	 */
	public function test() {
		self::verify_nonce( __FUNCTION__ );

		wp_send_json_success();
	}
}

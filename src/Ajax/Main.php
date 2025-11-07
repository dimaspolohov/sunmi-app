<?php
/**
 * Main ajax class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\Ajax;

defined( 'ABSPATH' ) || exit();

/**
 * Main ajax class
 *
 * @package Onepix\PluginTemplate
 */
class Main {
	/**
	 * Ajax constructor.
	 */
	public function __construct() {
		add_action(
			'init',
			function () {
				new Example();
			}
		);
	}
}

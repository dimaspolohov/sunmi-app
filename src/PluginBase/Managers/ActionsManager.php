<?php
/**
 * Actions manager class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\PluginBase\Managers;

defined( 'ABSPATH' ) || exit();

/**
 * Actions manager class
 *
 * @package Onepix\PluginTemplate
 */
abstract class ActionsManager {
	/**
	 * Register plugin actions.
	 */
	public static function register() {
		add_action( 'init', array( __CLASS__, 'action_init' ), 100 );
		add_action( 'plugins_loaded', array( __CLASS__, 'plugins_loaded' ), 100 );
	}

	/**
	 * Handler for 'init' action.
	 */
	public static function action_init() {
	}

	/**
	 * Handler for 'plugins_loaded' action.
	 */
	public static function plugins_loaded() {
	}
}

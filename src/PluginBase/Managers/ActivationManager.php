<?php
/**
 * Activation manager class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\PluginBase\Managers;

/**
 * Activation manager class
 *
 * @package Onepix\PluginTemplate
 */
abstract class ActivationManager {
	/**
	 * Register actions.
	 *
	 * @param string $plugin_file Path to main plugin file. Use __FILE__ const in main file to get it.
	 */
	public static function register( string $plugin_file ) {
		register_activation_hook( $plugin_file, array( __CLASS__, 'activation_hook' ) );
		register_deactivation_hook( $plugin_file, array( __CLASS__, 'deactivation_hook' ) );
	}

	/**
	 * Plugin activated hook
	 */
	public static function activation_hook() {
	}

	/**
	 * Plugin deactivated hook
	 */
	public static function deactivation_hook() {
		RewriteManager::flush_rewrite_rules( true );
	}
}

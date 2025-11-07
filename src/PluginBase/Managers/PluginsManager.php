<?php
/**
 * Plugins manager class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\PluginBase\Managers;

defined( 'ABSPATH' ) || exit();

/**
 * Plugins manager class
 *
 * Contains methods for checking third-party classes
 *
 * @package Onepix\PluginTemplate
 */
abstract class PluginsManager {
	/**
	 * Check if elementor plugin enabled
	 *
	 * @return bool
	 */
	public static function elementor_enabled(): bool {
		return class_exists( 'Elementor\Plugin' );
	}

	/**
	 * Check if acf blocks enabled
	 *
	 * @return bool
	 */
	public static function acf_blocks_enabled(): bool {
		return function_exists( 'acf_register_block' );
	}
}

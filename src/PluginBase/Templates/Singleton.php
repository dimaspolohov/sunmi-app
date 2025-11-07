<?php
/**
 * Singleton template class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\PluginBase\Templates;

defined( 'ABSPATH' ) || exit();

/**
 * Singleton template class
 *
 * @package Onepix\PluginTemplate
 */
abstract class Singleton {
	/**
	 * Singleton instances
	 *
	 * @var array
	 */
	private static array $instances = array();

	/**
	 * Singleton constructor.
	 */
	protected function __construct() { }

	/**
	 * Get singleton instance
	 *
	 * @return Singleton
	 */
	public static function get_instance(): Singleton {
		if ( ! isset( self::$instances[ static::class ] ) ) {
			self::$instances[ static::class ] = new static();
		}

		return self::$instances[ static::class ];
	}
}

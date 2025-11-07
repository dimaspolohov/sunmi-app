<?php
/**
 * Rewrite manager class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\PluginBase\Managers;

/**
 * Rewrite manager class
 *
 * @package Onepix\PluginTemplate
 */
abstract class RewriteManager {
	/**
	 * Flush rewrite rules if it was not flashed
	 *
	 * @param  bool $disabling_plugin if true set option 'rules_flushed to false. If false flush rules if not flushed.
	 *
	 * @return void
	 */
	public static function flush_rewrite_rules( bool $disabling_plugin = false ) {
		if ( $disabling_plugin ) {
			flush_rewrite_rules( false );
			self::set_rewrite_rules_flashed( false );
		} elseif ( ! self::was_rewrite_rules_flashed() ) {
			flush_rewrite_rules( false );
			self::set_rewrite_rules_flashed( true );
		}
	}

	/**
	 * Set flushed or not rewrite rules
	 *
	 * @param  bool $value was rules flushed.
	 */
	public static function set_rewrite_rules_flashed( bool $value ) {
		update_option(
			self::get_rewrite_rules_flushed_option_key(),
			$value ? '1' : '0'
		);
	}

	/**
	 * Check if rewrite rules was flushed
	 *
	 * @return bool
	 */
	public static function was_rewrite_rules_flashed(): bool {
		return get_option( self::get_rewrite_rules_flushed_option_key() ) === '1';
	}

	/**
	 * Get rewrite rules option key
	 *
	 * @return string
	 */
	public static function get_rewrite_rules_flushed_option_key(): string {
		return plugin_template()->get_option( 'id' ) . '_rewrite_rules_flushed';
	}
}

<?php
/**
 * Assets manager class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\PluginBase\Managers;

defined( 'ABSPATH' ) || exit();

/**
 * Assets manager class
 *
 * @package Onepix\PluginTemplate
 */
class AssetsManager {
	/**
	 * Prefix for styles and scripts
	 *
	 * @var string
	 */
	protected string $prefix;

	/**
	 * Files source
	 *
	 * @var string
	 */
	protected string $assets_src;

	/**
	 * Files version
	 *
	 * @var string
	 */
	protected string $version;

	/**
	 * Assets constructor.
	 */
	public function __construct() {
		$this->prefix     = (string) plugin_template()->get_option( 'id' );
		$this->assets_src = (string) plugin_template()->get_option( 'assets_url' );
		$this->version    = (string) plugin_template()->get_option( 'version' );
	}

	/**
	 * Method for registration default styles and scripts.
	 */
	public static function register() {
		add_action(
			'wp_enqueue_scripts',
			function () {
				plugin_template()
				->assets()
				->enqueue_style( 'frontend', 'frontend' )
				->enqueue_script( 'frontend', 'frontend', array(), array(), true );
			}
		);

		add_action(
			'admin_enqueue_scripts',
			function () {
				plugin_template()
				->assets()
				->enqueue_style( 'admin', 'admin' )
				->enqueue_script( 'admin', 'admin', array(), array(), true );
			}
		);
	}

	/**
	 * Returns handle with plugin prefix.
	 *
	 * @param  string $handle script or style id.
	 *
	 * @return string
	 */
	public function get_handle( string $handle ): string {
		return "{$this->prefix}-$handle";
	}

	/**
	 * Register plugin styles file.
	 *
	 * @param  string $handle style id.
	 * @param  string $src style path.
	 * @param  array  $deps an array of registered styles handles this script depends on.
	 *
	 * @return $this
	 */
	public function register_style( string $handle, string $src, $deps = array() ): AssetsManager {
		$suffix = '.min';

		wp_register_style(
			$this->get_handle( $handle ),
			"{$this->assets_src}css/$src$suffix.css",
			$deps,
			$this->version
		);

		return $this;
	}

	/**
	 * Enqueue style. Also register script if $args sent
	 *
	 * @param  string $handle style handle.
	 * @param mixed  ...$args arguments for register_style method.
	 *
	 * @return $this
	 */
	public function enqueue_style( string $handle, ...$args ): AssetsManager {
		if ( count( $args ) ) {
			$this->register_style( $handle, ...$args );
		}

		wp_enqueue_style( $this->get_handle( $handle ) );

		return $this;
	}

	/**
	 * Register plugin script.
	 *
	 * @param  string $handle script handle.
	 * @param  string $src script path.
	 * @param  array  $deps an array of registered script handles this script depends on.
	 * @param  array  $localizations data to be registered via wp_localize_script.
	 * @param  bool  $set_script_translation add localization to script via wp_set_script_translations.
	 *
	 * @return $this
	 */
	public function register_script( string $handle, string $src, array $deps = array(), array $localizations = array(), bool $set_script_translation = true ): AssetsManager {
		$suffix = '.min';

		wp_register_script(
			$this->get_handle( $handle ),
			"{$this->assets_src}js/$src$suffix.js",
			$deps,
			$this->version,
			true
		);

		foreach ( $localizations as $object_name => $data ) {
			wp_localize_script(
				$this->get_handle( $handle ),
				$object_name,
				$data
			);
		}

		if ( $set_script_translation ) {
			wp_set_script_translations(
				$this->get_handle( $handle ),
				'plugin-template',
				plugin_template()->get_option( 'plugin_path' )  . 'languages'
			);
		}

		return $this;
	}

	/**
	 * Enqueue script. Also register script if $args sent
	 *
	 * @param string $handle script handle.
	 * @param mixed  ...$args arguments for register_script method.
	 *
	 * @return $this
	 */
	public function enqueue_script( string $handle, ...$args ): AssetsManager {
		if ( count( $args ) ) {
			$this->register_script( $handle, ...$args );
		}

		wp_enqueue_script( $this->get_handle( $handle ) );

		return $this;
	}
}

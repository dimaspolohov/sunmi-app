<?php
/**
 * Plugin name: SunMi API
 * Description: SunMi printer app REST API integration
 * Domain Path: /languages
 * Text Domain: lieferchef-sunmi
 * Version: 0.1.1
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate;

use Onepix\PluginTemplate\PluginBase\Managers\ActionsManager;
use Onepix\PluginTemplate\PluginBase\Managers\ActivationManager;
use Onepix\PluginTemplate\PluginBase\Managers\AssetsManager;
use Onepix\PluginTemplate\PluginBase\Managers\RewriteManager;
use Onepix\PluginTemplate\PluginBase\Templates\Singleton;
use Onepix\PluginTemplate\WordPressCore\AdminPages\Main as AdminPages;
use Onepix\PluginTemplate\WordPressCore\AdminPages\Base as AdminPagesBase;

defined( 'ABSPATH' ) || exit();

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Main plugin class
 *
 * @package Onepix\PluginTemplate
 */
class SunMi extends Singleton {
	/**
	 * Plugin id
	 *
	 * @var string
	 */
	private string $plugin_id = 'sunmi';

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private string $version = '0.1.1';

	/**
	 * Plugin url
	 *
	 * @var string
	 */
	private string $plugin_url;

	/**
	 * Plugin path
	 *
	 * @var string
	 */
	private string $plugin_path;

	/**
	 * Plugin main file name with plugin dir name
	 *
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * Assets url
	 *
	 * @var string
	 */
	private string $assets_url;

	/**
	 * Templates path
	 *
	 * @var string
	 */
	private string $templates_path;

	/**
	 * Is current run for testing
	 *
	 * @var string
	 */
	private string $is_testing;

	/**
	 * Settings array
	 *
	 * @var array|null
	 */
	private ?array $plugin_options = null;

	/**
	 * Assets class instance
	 *
	 * @var AssetsManager|null
	 */
	private ?AssetsManager $assets = null;

	/**
	 * Admin pages class to get settings from pages
	 *
	 * @var AdminPages|null
	 */
	private ?AdminPages $admin_pages = null;

	/**
	 * Main constructor.
	 */
	protected function __construct() {
		$this->is_testing = defined( 'WP_TESTS_RUNNING' ) && WP_TESTS_RUNNING;

		$this->plugin_url     = plugin_dir_url( __FILE__ );
		$this->plugin_path    = plugin_dir_path( __FILE__ );
		$this->assets_url     = $this->plugin_url . 'assets/dist/';
		$this->templates_path = $this->plugin_path . ( $this->is_testing ? 'tests/templates/' : 'templates/' );
		$this->plugin_file    = __FILE__;

		load_plugin_textdomain( $this->plugin_id, false, "$this->plugin_id/languages/" );

		new WordPressCore\Main();
		new Ajax\Main();
		new REST\Main();
		new Pushy\Main();

		ActivationManager::register( $this->plugin_file );
		ActionsManager::register();
		AssetsManager::register();

		add_action(
			'plugins_loaded',
			function () {
				$this->admin_pages = new AdminPages();
			}
		);

		add_action(
			'init',
			function () {
				// Rewrite rules must be flushed after full plugin activation.
				// Rules will not be flushed properly on activation hook.
				// Remove this action if plugin doesn't add pages to frontend.
				RewriteManager::flush_rewrite_rules();
			},
			0,
			PHP_INT_MAX
		);
	}

	/**
	 * Get plugin settings
	 *
	 * @param  string $name  Setting key.
	 *
	 * @return mixed
	 */
	public function get_option( string $name ) {
		// Init plugin options.
		if ( empty( $this->plugin_options ) ) {
			$this->plugin_options = array(
				'id'             => $this->plugin_id,
				'plugin_id'      => $this->plugin_id,
				'version'        => $this->version,
				'plugin_url'     => $this->plugin_url,
				'plugin_path'    => $this->plugin_path,
				'plugin_file'    => $this->plugin_file,
				'assets_url'     => $this->assets_url,
				'templates_path' => $this->templates_path,
				'is_testng'      => $this->is_testing,
			);
		}

		// Return plugin option.
		if ( isset( $this->plugin_options[ $name ] ) ) {
			return $this->plugin_options[ $name ];
		}

		return null;
	}

	/**
	 * Get AssetsManager instance
	 *
	 * @return AssetsManager
	 */
	public function assets(): AssetsManager {
		if ( is_null( $this->assets ) ) {
			$this->assets = new AssetsManager();
		}

		return $this->assets;
	}

	/**
	 * Get one of Pages object
	 *
	 * @param  string $page_slug page slug.
	 *
	 * @return AdminPagesBase|null
	 */
	public function admin_pages( string $page_slug ): ?object {
		return $this->admin_pages->get( $page_slug );
	}

	/**
	 * Check if template exists in templates directory
	 *
	 * @param  string $template template path.
	 *
	 * @return bool
	 */
	public function template_exists( string $template ): bool {
		if ( ! str_ends_with( $template, '.php' ) ) {
			$template .= '.php';
		}

		return file_exists( $this->templates_path . $template );
	}

	/**
	 * Include template from templates directory
	 *
	 * @param string $template Template name.
	 * @param array  $args     Arguments.
	 * @param bool   $echo     Return or echo. Echo by default.
	 *
	 * @return bool|string
	 */
	public function include_template( string $template, $args = array(), $echo = true ) {
		if ( ! str_ends_with( $template, '.php' ) ) {
			$template .= '.php';
		}

		$template_path = $this->templates_path . $template;

		if ( file_exists( $template_path ) ) {
			ob_start();

			extract( $args ); //phpcs:ignore WordPress.PHP.DontExtract.extract_extract

			include $template_path;

			$template_body = ob_get_clean();
		} elseif ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ) {
			$template_body = "Template '$template' not found";
		} else {
			$template_body = '';
		}

		if ( $echo ) {
			echo $template_body; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return $template_body;
	}

	/**
	 * Add data to WP logs
	 *
	 * @param  mixed  $data  Data to add to logs.
	 * @param  string $code_source  Source of log in code.
	 *
	 * @author Daniel Dubchenko
	 */
	public function log( mixed $data, string $code_source = '' ): void {
		if ( empty( $code_source ) ) {
			$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 )[1]; //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace

			$code_source  = isset( $backtrace['class'] ) ? $backtrace['class'] . '::' : '';
			$code_source .= $backtrace['function'] ?? '';
		}

		$data = array(
			'source' => "$this->plugin_id: $code_source",
			'data'   => $data,
		);

		error_log( print_r( $data, true ) ); //phpcs:ignore
	}
}

require_once __DIR__ . '/main-class-shortcut.php';

plugin_template();

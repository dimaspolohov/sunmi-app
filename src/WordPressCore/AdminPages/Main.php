<?php
/**
 * Main settings class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\WordPressCore\AdminPages;

defined( 'ABSPATH' ) || exit();

/**
 * Main settings class
 *
 * @package Onepix\PluginTemplate
 */
class Main {
	/**
	 * Pages array
	 *
	 * @var Base[]
	 */
	private array $pages = array();

	/**
	 * Main constructor.
	 */
	public function __construct() {
		$this->pages['settings'] = new Settings();
	}

	/**
	 * Get page by slug
	 *
	 * @param string $page_slug page slug to get page.
	 *
	 * @return Base|null
	 */
	public function get( string $page_slug ): ?Base {
		return $this->pages[ $page_slug ] ?? null;
	}
}

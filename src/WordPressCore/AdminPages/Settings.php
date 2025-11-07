<?php
/**
 * Settings page class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\WordPressCore\AdminPages;

defined( 'ABSPATH' ) || exit();

/**
 * Settings page class
 *
 * @package Onepix\PluginTemplate
 */
class Settings extends Base {
	/**
	 * Option and menu slug. Must be overwritten in child class
	 *
	 * @var string
	 */
	protected string $slug = 'settings';

	/**
	 * User capability to see page.
	 *
	 * @var string
	 */
	protected string $capability = 'edit_posts';

	/**
	 * Split settings into tabs
	 *
	 * @var bool
	 */
	protected bool $tabs_enabled = false;

	/**
	 * Split settings into tabs
	 *
	 * @var bool
	 */
	protected bool $tabs_on_same_page = false;

	/**
	 * Settings constructor.
	 */
	public function __construct() {
		$this->sections = array(
			'print_data' => array(
				'field_name' => __( 'Print data', 'lieferchef-sunmi' ),
				'tab'        => 'main',
				'fields'     => array(
					'footer' => array(
						'type'  => 'text',
						'title' => __( 'Footer text', 'lieferchef-sunmi' ),
						'default' => __( 'Enjoy your meal!', 'lieferchef-sunmi' ),
					),
					'logo' => array(
						'type'  => 'image',
						'title' => __( 'Logo', 'lieferchef-sunmi' ),
					),
                    'font_size_address' => array(
                        'type'  => 'number',
                        'title' => __( 'Address font size (default 27)', 'lieferchef-sunmi' ),
                        'default' => 27,
                    ),
                    'font_size_products' => array(
                        'type'  => 'number',
                        'title' => __( 'Products list font size (default 27)', 'lieferchef-sunmi' ),
                        'default' => 27,
                    ),
                    'font_size_payment' => array(
                        'type'  => 'number',
                        'title' => __( 'Payment font size (default 27)', 'lieferchef-sunmi' ),
                        'default' => 27,
                    ),
				),
			),
		);

		$this->tabs = array(
			'main' => __( 'Main settings', 'lieferchef-sunmi' ),
		);

		parent::__construct();
	}

	/**
	 * Register submenu in tools menu
	 */
	public function create_menu() {
		add_menu_page(
			__( 'SunMi Printer', 'lieferchef-sunmi' ),
			__( 'SunMi Printer', 'lieferchef-sunmi' ),
			$this->capability,
			$this->menu_page_slug,
			array( $this, 'print_page' ),
			'dashicons-admin-settings',
			58
		);
	}
}

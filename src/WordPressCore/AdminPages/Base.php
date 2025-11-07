<?php
/**
 * Pages base class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\WordPressCore\AdminPages;

defined( 'ABSPATH' ) || exit();

/**
 * Pages base class
 *
 * @package Onepix\PluginTemplate
 */
abstract class Base {
	/**
	 * Option and menu slug. Must be overwritten in child class
	 *
	 * @var string
	 */
	protected string $slug;

	/**
	 * Option name
	 *
	 * @var string
	 */
	protected string $option_name;

	/**
	 * Full menu slug.
	 *
	 * @var string
	 */
	protected string $menu_page_slug;

	/**
	 * Templates path
	 *
	 * @var string
	 */
	protected string $templates_path;

	/**
	 * Templates path
	 *
	 * @var string
	 */
	protected string $fields_path;

	/**
	 * Templates path
	 *
	 * @var string
	 */
	protected string $global_fields_path = 'admin/pages/global-fields/';

	/**
	 * User capability to see page.
	 *
	 * @var string
	 */
	protected string $capability = 'manage_options';

	/**
	 * Array of sections and fields to register
	 *
	 * @var array
	 */
	protected array $sections = array();

	/**
	 * Split settings into tabs
	 *
	 * @var bool
	 */
	protected bool $tabs_enabled = false;

	/**
	 * Render all tabs and toggle them via js
	 *
	 * @var bool
	 */
	protected bool $tabs_on_same_page = false;

	/**
	 * Array of tabs, tab_slug => tab_name
	 *
	 * @var array
	 */
	protected array $tabs = array();

	/**
	 * Main tab slug
	 *
	 * @var string
	 */
	protected string $main_tab = '';

	/**
	 * Main tab slug
	 *
	 * @var string
	 */
	protected string $current_tab = '';

	/**
	 * Base constructor.
	 */
	public function __construct() {
		$this->option_name = plugin_template()->get_option( 'id' ) . '-' . $this->slug;

		$this->menu_page_slug = plugin_template()->get_option( 'id' ) . '-' . $this->slug . '-page';
		$this->templates_path = "pages/$this->slug/";
		$this->fields_path    = $this->templates_path . 'fields/';

		$this->main_tab    = $this->tabs_enabled && empty( $this->main_tab ) ? array_key_first( $this->tabs ) ?: '' : '';
		$this->current_tab = $this->tabs_enabled && empty( $this->current_tab ) && ! empty( $_GET['tab'] ) ? static::clean_field( wp_unslash( $_GET['tab'] ) ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		add_action( 'admin_menu', array( $this, 'create_menu' ), 10 );
		add_action( 'admin_init', array( $this, 'redirect_to_main_tab' ), 5 );
		add_action( 'admin_init', array( $this, 'register_setting' ), 10 );
		add_action( 'admin_init', array( $this, 'create_settings_fields' ), 100 );

		add_filter(
			"option_page_capability_{$this->option_name}",
			function () {
				return $this->capability;
			}
		);

		if ( $this->is_current_page() ) {
			add_action(
				'admin_enqueue_scripts',
				function () {
					wp_enqueue_media();

					plugin_template()
					->assets()
					->enqueue_script( 'admin_pages', 'admin_pages', array(), array(), true );
				}
			);
		}
	}

	/**
	 * Register submenu in menu
	 *
	 * @see add_menu_page
	 * @see add_submenu_page
	 * @see print_page for $callback
	 * @see https://developer.wordpress.org/resource/dashicons for icons
	 */
	public function create_menu() {
	}

	/**
	 * Print page content
	 *
	 * @see get_admin_page_title
	 * @see do_settings_sections
	 * @see submit_button
	 */
	public function print_page() {
		plugin_template()->include_template( "{$this->templates_path}page.php" );
	}

	/**
	 * Register settings
	 *
	 * @see add_settings_section
	 * @see add_settings_field
	 */
	public function create_settings_fields() {
		foreach ( $this->sections as $section_id => $section ) {
			if ( $this->tabs_enabled && ! $this->tabs_on_same_page && ! $this->is_current_tab( $section['tab'] ?? '' ) ) {
				continue;
			}

			if ( $this->tabs_enabled && $this->tabs_on_same_page ) {
				if ( empty( $section['args'] ) ) {
					$section['args'] = array();
				}

				$section['args']['before_section'] = "<div class='section hidden' data-tab='{$section['tab']}'>" . ( $section['args']['before_section'] ?? '' );
				$section['args']['after_section']  = ( $section['args']['after_section'] ?? '' ) . '</div>';
			}

			$this->add_settings_section(
				$section_id,
				$section['title'] ?? '',
				$section['callback'] ?? null,
				$section['args'] ?? array()
			);

			foreach ( $section['fields'] ?? array() as $field_id => $field ) {
				$field['args']                = $field['args'] ?? array();
				$field['args']['default']     = $field['default'] ?? '';
				$field['args']['description'] = $field['description'] ?? '';
				$field['args']['values']      = $field['values'] ?? array();

				$this->add_settings_field(
					"{$section_id}_{$field_id}",
					$field['type'] ?? 'text',
					$field['title'] ?? '',
					$section_id,
					$field['args'] ?? array()
				);
			}
		}
	}

	/**
	 * Redirect user to main tab if current tab doesn't exist
	 */
	public function redirect_to_main_tab() {
		if ( $this->is_current_page() && $this->tabs_enabled && ! in_array( $this->current_tab, array_keys( $this->tabs ), true ) ) {
			wp_redirect( $this->get_page_url( $this->main_tab ) );
			die;
		}
	}

	/**
	 * Wrapper for register_setting
	 *
	 * @see \register_setting
	 *
	 * @see register_setting
	 */
	public function register_setting() {
		register_setting(
			$this->option_name,
			$this->option_name,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize settings
	 *
	 * @param array|null $settings setting value to sanitize.
	 *
	 * @return array
	 */
	public function sanitize_settings( ?array $settings ): array {
		$sanitized_settings = $this->get_option() ?: array();

		if ( empty( $settings ) ) {
			return $sanitized_settings;
		}

		foreach ( $this->sections as $section_id => $section ) {
			foreach ( $section['fields'] ?? array() as $field_id => $field ) {
				$field_id = "{$section_id}_{$field_id}";

				if ( isset( $settings[ $field_id ] ) ) {
					$sanitized_settings[ $field_id ] = $this->sanitize_setting(
						$settings[ $field_id ],
						$field_id,
						$field['type'] ?? 'text'
					);
				} elseif ( empty( $sanitized_settings[ $field_id ] ) ) {
						$sanitized_settings[ $field_id ] = $field['default'] ?? '';
				}
			}
		}

		return $sanitized_settings;
	}

	/**
	 * Sanitize field
	 *
	 * @param string $value field value.
	 * @param string $field_id field id.
	 * @param string $type field type.
	 *
	 * @return string
	 */
	public function sanitize_setting( string $value, string $field_id, string $type ): string {
		if ( method_exists( $this, "sanitize_setting_$type" ) ) {
			return $this->{"sanitize_setting_$type"}( $value, $field_id );
		}

		return $this->sanitize_setting_text( $value );
	}

	/**
	 * Sanitize text field
	 *
	 * @param string $value value to sanitize.
	 *
	 * @return string
	 */
	public function sanitize_setting_text( string $value ): string {
		return wp_kses_post( trim( stripslashes( $value ) ) );
	}

	/**
	 * Wrapper for add_settings_section
	 *
	 * @see \add_settings_section
	 *
	 * @param string        $id             Slug-name to identify the section. Used in the 'id' attribute of tags.
	 * @param string        $title          Formatted title of the section. Shown as the heading for the section.
	 * @param callable|null $callback       Function that echos out any content at the top of the section (between heading and fields).
	 * @param array         $args           {
	 *         Arguments used to create the settings section.
	 *
	 *     @type string $before_section HTML content to prepend to the section's HTML output.
	 *                                  Receives the section's class name as `%s`. Default empty.
	 *     @type string $after_section  HTML content to append to the section's HTML output. Default empty.
	 *     @type string $section_class  The class name to use for the section. Default empty.
	 * }
	 */
	protected function add_settings_section( string $id, string $title = '', ?callable $callback = null, $args = array() ) {
		add_settings_section(
			$this->option_name . '-' . $id,
			$title,
			$callback,
			$this->menu_page_slug,
			$args
		);
	}

	/**
	 * Wrapper for add_settings_field
	 *
	 * @see \add_settings_field
	 *
	 * @param string $id      Slug-name to identify the field. Used in the 'id' attribute of tags.
	 * @param string $type    Type of setting field to render it from $template_path.
	 * @param string $title   Formatted title of the field. Shown as the label for the field during output.
	 * @param string $section The slug-name of the section of the settings page in which to show the box.
	 * @param array  $args    Optional. Extra arguments that get passed to the callback function.
	 */
	protected function add_settings_field( string $id, string $type, string $title, string $section, $args = array() ) {
		$field_id = $this->option_name . '-' . $section . '-' . $id;

		add_settings_field(
			$field_id,
			"<label for='$field_id'>$title</label>",
			function ( $args ) use ( $type ) {
				$this->setting_field_template( $type, $args );
			},
			$this->menu_page_slug,
			$this->option_name . '-' . $section,
			wp_parse_args(
				$args,
				array(
					'id'    => $field_id,
					'name'  => "{$this->option_name}[$id]",
					'value' => $this->get_option( $id ) ?? ( $args['default'] ?? '' ),
				)
			)
		);
	}

	/**
	 * Print tabs section
	 *
	 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	 */
	public function print_tabs() {
		if ( ! $this->tabs_enabled ) {
			return;
		}

		$tabs_on_same_page = $this->tabs_on_same_page ? 'true' : 'false';
		echo "<nav class='nav-tab-wrapper woo-nav-tab-wrapper' data-js-tabs='$tabs_on_same_page' >";

		foreach ( $this->tabs as $tab_slug => $tab_name ) {
			if ( ! $this->tabs_on_same_page && $this->is_current_tab( $tab_slug ) ) {
				printf(
					'<span class="nav-tab nav-tab-active">%s</span>',
					$tab_name
				);
			} else {
				printf(
					'<a href="%1$s" class="nav-tab" data-tab="%2$s">%3$s</a>',
					$this->get_page_url( $tab_slug ),
					$tab_slug,
					$tab_name
				);
			}
		}

		echo '</nav>';
	}

	/**
	 * Wrapper for settings_fields and do_settings_sections
	 *
	 * @see \settings_fields
	 * @see \do_settings_sections
	 */
	public function do_settings_section() {
		settings_fields( $this->option_name );
		do_settings_sections( $this->menu_page_slug );
	}

	/**
	 * Print field template.
	 *
	 * @param string $type template name.
	 * @param array  $args template args.
	 */
	protected function setting_field_template( string $type, array $args ) {
		plugin_template()->include_template(
			plugin_template()->template_exists( "{$this->fields_path}{$type}.php" ) ?
				"{$this->fields_path}{$type}.php" :
				"{$this->global_fields_path}{$type}.php",
			$args,
		);
	}

	/**
	 * Get option(s) from page.
	 *
	 * @param string $field Optional. Field name to get. Default settings array.
	 * @param string $section Optional. Field section name. May be specified in $field as "{$section}_{$field}".
	 *
	 * @return mixed
	 */
	public function get_option( string $field = '', string $section = '' ) {
		if ( ! empty( $section ) ) {
			$field = "{$section}_{$field}";
		}

		$option = get_option( $this->option_name );

		if ( empty( $field ) ) {
			return $option;
		}

		if ( ! is_array( $option ) || ! isset( $option[ $field ] ) ) {
			return null;
		}

		if ( 'yes' === $option[ $field ] || 'no' === $option[ $field ] ) {
			return 'yes' === $option[ $field ];
		}

		return $option[ $field ];
	}

	/**
	 * Check if this page is current page.
	 *
	 * @return bool
	 */
	public function is_current_page(): bool {
		return isset( $_GET['page'] ) && $this->menu_page_slug === $_GET['page'];
	}

	/**
	 * Check if $tab is current tab.
	 *
	 * @param string $tab tab to check.
	 *
	 * @return bool
	 */
	public function is_current_tab( string $tab ): bool {
		return $this->tabs_enabled && $this->is_current_page() && $tab === $this->current_tab;
	}

	/**
	 * Check if current tab is main tab.
	 *
	 * @return bool
	 */
	public function is_main_tab(): bool {
		return $this->is_current_tab( $this->main_tab );
	}

	/**
	 * Get page url.
	 *
	 * @param  string $tab specify tab in url.
	 *
	 * @return string
	 */
	public function get_page_url( $tab = '' ): string {
		return get_admin_url(
			null,
			sprintf(
				'admin.php?page=%s%s',
				$this->menu_page_slug,
				! empty( $tab ) ? "&tab=$tab" : ''
			)
		);
	}

	/**
	 * Clean variable.
	 *
	 * @param mixed $value value to clean.
	 *
	 * @return mixed
	 */
	public static function clean_field( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( static::class, 'clean_field' ), $value );
		} else {
			return is_scalar( $value ) ? sanitize_text_field( $value ) : $value;
		}
	}
}

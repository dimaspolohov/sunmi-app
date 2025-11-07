<?php

use \Onepix\PluginTemplate\WordPressCore\AdminPages\Base as SettingsBase;

/**
 * AssetsManagerTest.
 *
 * @covers \Onepix\PluginTemplate\PluginBase\Managers\AssetsManager
 */
class AdminPagesBaseTest extends WP_UnitTestCase {
	const SETTINGS_SLUG = 'dummy-settings';

	public static $plugin_id = '';

	/**
	 * Set up before the test class runs.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		self::$plugin_id = plugin_template()->get_option( 'id' );
	}

	/**
	 * @backupGlobals true
	 */
	public function test__construct() {
		$_GET['tab'] = 'general';

		$properties = ( new DummySettingsTabsSamePage() )->get_properties();

		$this->assertSame( self::SETTINGS_SLUG, $properties->slug );
		$this->assertSame( self::$plugin_id . '-' . self::SETTINGS_SLUG, $properties->option_name );
		$this->assertSame( self::$plugin_id . '-' . self::SETTINGS_SLUG . '-page', $properties->menu_page_slug );
		$this->assertSame( 'pages/' . self::SETTINGS_SLUG . '/', $properties->templates_path );
		$this->assertSame( 'pages/' . self::SETTINGS_SLUG . '/fields/', $properties->fields_path );
		$this->assertSame( 'main', $properties->main_tab );
		$this->assertSame( 'general', $properties->current_tab );
	}

	public function test_print_page() {
		$this->expectOutputString( '<div class="wrap">Test Settings Page</div>' );
		( new DummySettings() )->print_page();
	}

	public function test_create_settings_fields() {
		$this->markTestIncomplete();
	}

	public function test_redirect_to_main_tab() {
		$this->markTestIncomplete();
	}

	public function test_register_setting() {
		$this->markTestIncomplete();
	}

	public function test_sanitize_settings() {
		$this->markTestIncomplete();
	}

	public function test_sanitize_setting() {
		$this->markTestIncomplete();
	}

	public function test_sanitize_setting_text() {
		$this->markTestIncomplete();
	}

	public function test_add_settings_section() {
		$this->markTestIncomplete();
	}

	public function test_add_settings_field() {
		$this->markTestIncomplete();
	}

	public function test_print_tabs() {
		$this->markTestIncomplete();
	}

	public function test_do_settings_section() {
		$this->markTestIncomplete();
	}

	public function test_setting_field_template() {
		$this->markTestIncomplete();
	}

	public function test_get_option() {
		$option_name = self::$plugin_id . '-' . self::SETTINGS_SLUG;

		$options = [
			'int'            => 1234,
			'string'         => 'test string',
			'bool_true'      => true,
			'bool_false'     => false,
			'string_yes'     => 'yes',
			'string_no'      => 'no',
			'section_option' => [
				'1' => '2',
			],
		];

		$settings = new DummySettings();

		$this->assertNull( $settings->get_option( 'int' ) );

		update_option( $option_name, $options );

		$this->assertSame( $options, $settings->get_option() );
		$this->assertSame( $options['int'], $settings->get_option( 'int' ) );
		$this->assertSame( $options['string'], $settings->get_option( 'string' ) );
		$this->assertSame( $options['bool_true'], $settings->get_option( 'bool_true' ) );
		$this->assertSame( $options['bool_false'], $settings->get_option( 'bool_false' ) );
		$this->assertSame( true, $settings->get_option( 'string_yes' ) );
		$this->assertSame( false, $settings->get_option( 'string_no' ) );
		$this->assertSame( $options['section_option'], $settings->get_option( 'section_option' ) );
		$this->assertSame( $options['section_option'], $settings->get_option( 'option', 'section' ) );
		$this->assertNull( $settings->get_option( 'undefined' ) );

		delete_option( $option_name );
	}

	/**
	 * @backupGlobals true
	 */
	public function test_is_current_page() {
		$_GET['page'] = self::$plugin_id . '-' . self::SETTINGS_SLUG . '-page';

		array_map(
			fn( $settings ) => $this->assertSame( true, $settings->is_current_page() ),
			[ new DummySettings(), new DummySettingsTabs(), new DummySettingsTabsSamePage() ]
		);

		$_GET['page'] = self::$plugin_id . '-not-' . self::SETTINGS_SLUG . '-page';

		array_map(
			fn( $settings ) => $this->assertSame( false, $settings->is_current_page() ),
			[ new DummySettings(), new DummySettingsTabs(), new DummySettingsTabsSamePage() ]
		);
	}

	public function test_is_current_tab_with_disabled_tabs() {
		$this->assertFalse( ( new DummySettings() )->is_current_page() );
	}

	/**
	 * @backupGlobals true
	 *
	 * @testWith
	 * ["tab", "tab", true]
	 * ["tab", "another-tab", false]
	 */
	public function test_is_current_tab_with_enabled_tabs( $get_param_tab, $tab_to_check, $result ) {
		$_GET['page'] = self::$plugin_id . '-' . self::SETTINGS_SLUG . '-page';
		$_GET['tab']  = $get_param_tab;

		$this->assertSame( $result, ( new DummySettingsTabs() )->is_current_tab( $tab_to_check ) );
		$this->assertSame( $result, ( new DummySettingsTabsSamePage() )->is_current_tab( $tab_to_check ) );
	}

	public function test_is_main_tab_with_disabled_tabs() {
		$this->assertFalse( ( new DummySettings() )->is_main_tab() );
	}

	/**
	 * @backupGlobals true
	 *
	 * @testWith
	 * ["main", true]
	 * ["not-main", false]
	 */
	public function test_is_main_tab_with_enabled_tabs( $get_param_tab, $result ) {
		$_GET['page'] = self::$plugin_id . '-' . self::SETTINGS_SLUG . '-page';
		$_GET['tab']  = $get_param_tab;

		$this->assertSame( $result, ( new DummySettingsTabs() )->is_main_tab() );
		$this->assertSame( $result, ( new DummySettingsTabsSamePage() )->is_main_tab() );
	}

	/**
	 * @testWith
	 * ["DummySettings"]
	 * ["DummySettingsTabs"]
	 * ["DummySettingsTabsSamePage"]
	 */
	public function test_get_page_url( $class ) {
		/**
		 * @var SettingsBase $settings
		 */
		$settings = new $class;

		$this->assertSame(
			get_admin_url( null, 'admin.php?page=' . self::$plugin_id . '-' . self::SETTINGS_SLUG . '-page' ),
			$settings->get_page_url()
		);

		$tab_name = 'general';

		$this->assertSame(
			get_admin_url( null, 'admin.php?page=' . self::$plugin_id . '-' . self::SETTINGS_SLUG . "-page&tab=$tab_name" ),
			$settings->get_page_url( $tab_name )
		);
	}

	/**
	 * @testWith
	 * [1234, "1234"]
	 * ["test string", "test string"]
	 * ["<h1>test header</h1>","test header"]
	 */
	public function test_clean_field( $value, $result ) {
		$this->assertSame(
			$result,
			SettingsBase::clean_field( $value )
		);
	}
}

class DummySettings extends SettingsBase {
	protected string $slug = AdminPagesBaseTest::SETTINGS_SLUG;
	protected string $capability = 'edit_posts';

	protected string $global_fields_path = 'admin/pages/global-fields/';

	public function __construct() {
		$this->sections = array(
			'section_name' => array(
				'field_name' => 'Field name',
				'tab'        => 'main',
				'fields'     => array(
					'key' => array(
						'type'  => 'text',
						'title' => 'Field name title',
					),
				),
			),
		);

		$this->tabs = array(
			'main' => 'Main settings',
		);

		parent::__construct();
	}

	public function get_properties() {
		return (object) get_object_vars( $this );
	}
}

class DummySettingsTabs extends DummySettings {
	protected bool $tabs_enabled = true;
}

class DummySettingsTabsSamePage extends DummySettingsTabs {
	protected bool $tabs_on_same_page = true;
}
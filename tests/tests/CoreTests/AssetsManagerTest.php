<?php

use \Onepix\PluginTemplate\PluginBase\Managers\AssetsManager;

/**
 * AssetsManagerTest.
 *
 * @covers \Onepix\PluginTemplate\PluginBase\Managers\AssetsManager
 */
class AssetsManagerTest extends WP_UnitTestCase {

	/**
	 * @var AssetsManager An instance of the AssetsManager class.
	 */
	private static AssetsManager $assets_manager;

	/**
	 * @var string Test style handle.
	 */
	private static string $test_style_handle = 'test-style';

	/**
	 * @var string Test style path.
	 */
	private static string $test_style_path = 'test-style.css';

	/**
	 * @var string Test script handle.
	 */
	private static string $test_script_handle = 'test-script';

	/**
	 * @var string Test script path.
	 */
	private static string $test_script_path = 'test-script.js';

	/**
	 * Set up before the test class runs.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		self::$assets_manager = new AssetsManager();
	}

	/**
	 * Clean up after each test.
	 */
	protected function tearDown(): void {
		parent::tearDown();

		wp_deregister_style( self::$test_style_handle );
		wp_deregister_style( self::$test_script_handle );
	}

	/**
	 * Test the get_handle method.
	 */
	public function test_get_handle() {
		$plugin_id = (string) plugin_template()->get_option( 'id' );
		$handle    = 'script-name';

		$this->assertSame(
			"{$plugin_id}-$handle",
			self::$assets_manager->get_handle( $handle )
		);
	}

	/**
	 * Test the register_style method.
	 */
	public function test_register_style() {
		self::$assets_manager->register_style( self::$test_style_handle, self::$test_style_path );

		$this->assertTrue( wp_style_is( self::$assets_manager->get_handle( self::$test_style_handle ), 'registered' ) );
	}

	/**
	 * Test the enqueue_style method.
	 */
	public function test_enqueue_style() {
		self::$assets_manager->enqueue_style( self::$test_style_handle, self::$test_style_path );

		$this->assertTrue( wp_style_is( self::$assets_manager->get_handle( self::$test_style_handle ), 'enqueued' ) );
	}

	/**
	 * Test the register_script method.
	 */
	public function test_register_script() {
		self::$assets_manager->register_script( self::$test_script_handle, self::$test_script_path );

		$this->assertTrue( wp_script_is( self::$assets_manager->get_handle( self::$test_script_handle ), 'registered' ) );
	}

	/**
	 * Test the register_script method with dependencies and localizations.
	 */
	public function test_register_script_with_deps_and_localizations() {
		$dependencies  = array( 'jquery' );
		$localizations = array( 'object_name' => array( 'key' => 'value' ) );
		$localizations_string = 'var object_name = {"key":"value"};';

		self::$assets_manager->register_script(
			self::$test_script_handle,
			self::$test_script_path,
			$dependencies,
			$localizations
		);

		$this->assertTrue( wp_script_is( self::$assets_manager->get_handle( self::$test_script_handle ), 'registered' ) );

		// Check dependencies.
		foreach ( $dependencies as $dependency ) {
			$this->assertTrue( wp_script_is( $dependency, 'registered' ) );
		}

		// Check localizations.
		$this->assertEquals( $localizations_string, wp_scripts()->get_data( self::$assets_manager->get_handle( self::$test_script_handle ), 'data' ) );
	}

	/**
	 * Test the register_script method with script translation.
	 */
	public function test_register_script_with_translation() {
		self::markTestSkipped('translations support is not completed');

		update_option('languages_path', '/path/to/languages');

		self::$assets_manager->register_script(
			self::$test_script_handle,
			self::$test_script_path,
			array(),
			array(),
			true
		);

		$this->assertTrue(wp_script_is(self::$assets_manager->get_handle(self::$test_script_handle), 'registered'));

		$this->assertEquals('plugin-template', wp_scripts()->get_data(self::$assets_manager->get_handle(self::$test_script_handle), 'textdomain'));
	}

	/**
	 * Test the enqueue_script method.
	 */
	public function test_enqueue_script() {
		self::$assets_manager->enqueue_script( self::$test_script_handle, self::$test_script_path );

		$this->assertTrue( wp_script_is( self::$assets_manager->get_handle( self::$test_script_handle ), 'enqueued' ) );
	}
}
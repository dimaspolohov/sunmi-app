<?php

use \Onepix\PluginTemplate\PluginBase\Templates\Singleton;

class DummySingleton extends Singleton {}

/**
 * SingletonTest.
 *
 * @covers \Onepix\PluginTemplate\PluginBase\Templates\Singleton
 */
class SingletonTest extends WP_UnitTestCase {
	/**
	 * Test if the Singleton class can be instantiated.
	 */
	public function test_singleton_instantiation() {
		$singleton = DummySingleton::get_instance();

		$this->assertInstanceOf(Singleton::class, $singleton);
		$this->assertTrue(DummySingleton::get_instance() === $singleton);
	}
}
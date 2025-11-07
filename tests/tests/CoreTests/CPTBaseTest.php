<?php

use Onepix\PluginTemplate\WordPressCore\CPT\Base;

/**
 * AssetsManagerTest.
 *
 * @covers \Onepix\PluginTemplate\PluginBase\Managers\AssetsManager
 */
class CPTBaseTest extends WP_UnitTestCase {
	public function test_cpt_registration() {
		$cpt = new DummyCPT();

		$cpt->register_post_type();

		$this->assertTrue( post_type_exists( DummyCPT::POST_TYPE ) );

		$cpt->unregister();

		$this->assertFalse( post_type_exists( DummyCPT::POST_TYPE ) );
	}
}

class DummyCPT extends Base {
	const POST_TYPE = 'dummy_post_type';

	public function get_registration_args(): array {
		return array();
	}
}
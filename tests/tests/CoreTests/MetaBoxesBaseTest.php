<?php

use Onepix\PluginTemplate\WordPressCore\MetaBoxes\Base;

/**
 * AssetsManagerTest.
 *
 * @covers \Onepix\PluginTemplate\PluginBase\Managers\AssetsManager
 */
class MetaBoxesBaseTest extends WP_UnitTestCase {
	public function test_register() {
		$meta_box = DummyMetaBox::register();

		$this->assertNotFalse( has_filter( 'add_meta_boxes', array( $meta_box, 'action_add_meta_box' ) ) );
		$this->assertNotFalse( has_filter( 'save_post', array( $meta_box, 'action_save_meta_box' ) ) );
		$this->assertNotFalse( has_filter( 'admin_enqueue_scripts', array( $meta_box, 'action_enqueue_assets' ) ) );
	}

	public function test_action_add_meta_box() {
		global $wp_meta_boxes;

		DummyMetaBox::register();

		do_action( 'add_meta_boxes' );

		$this->assertArrayHasKey( 'dummy', $wp_meta_boxes['posts']['normal']['default'] );
	}

	public function test_render_meta_box() {
		$this->expectOutputString('<div class="wrap">Test Meta Box</div>');

		( new DummyMetaBox )->render_meta_box( new WP_Post( (object) array() ) );
	}
}

class DummyMetaBox extends Base {
	protected string $id = 'dummy';
	protected string $title = 'Dummy';
	protected array $screens = array( 'posts' );
	protected string $context = 'normal';
	protected string $template = 'dummy';


	public function save_meta_box_data( $post_id ) {
	}
}
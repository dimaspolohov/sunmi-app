<?php
/**
 * Post class example.
 *
 * @package Onepix\PluginTemplate\WordPressCore\Posts
 */

namespace Onepix\PluginTemplate\WordPressCore\Posts;

defined( 'ABSPATH' ) || exit();

/**
 * Post class example.
 *
 * @package Onepix\PluginTemplate\WordPressCore\Posts
 */
class Example extends Base {
	/**
	 * Meta field name.
	 */
	const META_FIELD_NAME = '_meta_field_name';

	/**
	 * Get post content.
	 *
	 * @return string
	 */
	public function get_content(): string {
		return $this->post->post_content;
	}

	/**
	 * Save post content.
	 *
	 * @param  string $content content to save.
	 */
	public function set_content( string $content ) {
		$this->post->post_content = $content;

		wp_update_post( $this->post );
	}

	/**
	 * Get example meta.
	 *
	 * @return string
	 */
	public function get_example_meta(): string {
		return $this->post->{self::META_FIELD_NAME};
	}

	/**
	 * Save data to example meta field.
	 *
	 * @param  string $value value to save.
	 */
	public function set_example_meta( string $value ) {
		$this->update_meta( static::META_FIELD_NAME, $value );
	}
}

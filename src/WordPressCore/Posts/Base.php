<?php
/**
 * Base posts class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\WordPressCore\Posts;

use Exception;
use WP_Post;

defined( 'ABSPATH' ) || exit();

/**
 * Base posts class
 *
 * @package Onepix\PluginTemplate
 */
class Base {
	/**
	 * Post object.
	 *
	 * @var WP_Post
	 */
	protected WP_Post $post;


	/**
	 * Base constructor.
	 *
	 * @param  WP_Post|int|null $the_post  post object or id (or null to get global post).
	 *
	 * @throws Exception If post not found.
	 */
	public function __construct( mixed $the_post = null ) {
		$the_post  = get_post( $the_post );

		if ( is_null( $the_post ) ) {
			$this->post = get_post();
		} elseif ( is_object( $the_post ) ) {
			$this->post = $the_post;
		} elseif ( is_int( $the_post ) && $the_post > 0 ) {
			$this->post = get_post( $the_post );
		}

		if ( empty( $this->post ) ) {
			throw new Exception( 'Post not found ' . print_r( $the_post, true ) );
		}
	}

	/**
	 * Get post meta.
	 *
	 * @param string $key     The meta key to retrieve.
	 * @param bool   $single  Optional. Whether to return a single value.
	 *
	 * @return mixed
	 */
	protected function get_meta( string $key, $single = true ) {
		return get_post_meta( $this->post->ID, $key, $single );
	}

	/**
	 * Add post meta.
	 *
	 * @param  string $field metadata key.
	 * @param  mixed  $value value to save.
	 * @param  bool   $unique Whether the same key should not be added.
	 *
	 * @return int|false
	 */
	protected function add_meta( string $field, $value, bool $unique = false ) {
		return add_post_meta( $this->post->ID, $field, $value, $unique );
	}

	/**
	 * Update post meta.
	 *
	 * @param string $field metadata key.
	 * @param mixed  $value value to save.
	 * @param mixed  $prev_value Optional. Previous value to check before updating.
	 *
	 * @return int|bool
	 */
	protected function update_meta( string $field, $value, $prev_value = '' ) {
		return update_post_meta( $this->post->ID, $field, $value, $prev_value );
	}

	/**
	 * Delete post meta.
	 *
	 * @param string $field metadata key.
	 * @param mixed  $meta_value Optional. Metadata value. If provided.
	 *
	 * @return bool
	 */
	protected function delete_meta( string $field, $meta_value = '' ) {
		return delete_post_meta( $this->post->ID, $field, $meta_value );
	}
}

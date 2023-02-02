<?php

namespace Tarosky\VideoCollector\Pattern;


/**
 * Post type pattern.
 *
 * @property-read string $post_type    Post type name.
 * @property-read string $nonce_action Nonce action.
 * @property-read string $nonce_name   Nonce field name.
 */
abstract class PostTypePattern extends SingletonPattern {

	/**
	 * {@inheritdoc}
	 */
	protected function init() {
		add_action( 'init', [ $this, 'register' ] );
		add_action( 'do_meta_boxes', [ $this, 'do_meta_boxes' ] );
		add_action( 'save_post_' . $this->post_type_name(), [ $this, 'save_post' ], 10, 2 );
		add_filter( 'manage_' . $this->post_type_name() . '_posts_columns', [ $this, 'posts_columns' ], 10 );
		add_action( 'manage_' . $this->post_type_name() . '_posts_custom_column', [ $this, 'render_posts_column' ], 10, 2 );
	}

	/**
	 * Save post.
	 *
	 * @param int      $post_id post id.
	 * @param \WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function save_post( $post_id, $post ) {
		$input = filter_input( INPUT_POST, $this->nonce_name );
		if ( ! $input ) {
			return;
		}
		if ( ! wp_verify_nonce( $input, $this->nonce_action ) ) {
			return;
		}
		$this->update_post( $post );
	}

	/**
	 * Save post data.
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	protected function update_post( $post ) {
		// Save extra post data.
	}

	/**
	 * Register meta box.
	 *
	 * @param string $post_type
	 * @return void
	 */
	public function do_meta_boxes( $post_type ) {
		if ( $this->post_type_name() === $post_type ) {
			$this->meta_box();
		}
	}

	/**
	 * Register meta box.
	 *
	 * @return void
	 */
	protected function meta_box() {
		// Register meta box here.
	}

	/**
	 * Register post types.
	 *
	 * @return void
	 */
	public function register() {
		register_post_type( $this->post_type_name(), apply_filters( 'tsvc_post_type_args', $this->post_type_args(), $this->post_type_name() ) );
		$this->register_taxonomies();
	}

	/**
	 * Register taxonomies here.
	 *
	 * @return void
	 */
	protected function register_taxonomies() {
		// Register taxonomies here.
	}

	/**
	 * Get post type name.
	 *
	 * @return string
	 */
	abstract protected function post_type_name();

	/**
	 * Post type arguments.
	 *
	 * @return array
	 */
	abstract public function post_type_args();

	/**
	 * Render nonce field.
	 *
	 * @return void
	 */
	protected function nonce_field() {
		wp_nonce_field( $this->nonce_action, $this->nonce_name, false );
	}

	/**
	 * Post custom columns.
	 *
	 * @param string[] $columns Add columns.
	 * @return string[]
	 */
	public function posts_columns( $columns ) {
		return $columns;
	}

	/**
	 * Render admin column.
	 *
	 * @param string $column  Column name
	 * @param int    $post_id Post ID.
	 *
	 * @return void
	 */
	public function render_posts_column( $column, $post_id ) {
		// Do nothing.
	}

	/**
	 *
	 *
	 * @param string $name
	 *
	 * @return string|null
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'post_type':
				return $this->post_type_name();
			case 'nonce_action':
				return 'tsvc_update_' . $this->post_type_name();
			case 'nonce_name':
				return '_tsvcnonce' . $this->post_type_name();
			default:
				return null;
		}
	}
}

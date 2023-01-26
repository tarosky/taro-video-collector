<?php

namespace Tarosky\VideoCollector\Model;


use Tarosky\VideoCollector\Pattern\PostTypePattern;

/**
 * Video post type.
 */
class VideoPostType extends PostTypePattern {

	/**
	 * {@inheritdoc}
	 */
	protected function post_type_name() {
		return 'videos';
	}

	/**
	 * {@inheritdoc}
	 */
	public function post_type_args() {
		return [
			'labels' => [
				'name' => __( 'Videos', 'tsvc' ),
				'singular_name' => __( 'Video', 'tsvc' ),
			],
			'public' => false,
			'show_ui' => true,
			'show_in_nav_menus' => false,
			'show_in_admin_bar' => false,
			'show_in_rest' => false,
			'menu_icon' => 'dashicons-video-alt3',
			'supports' => [ 'title', 'excerpt', 'revisions' ],
		];
	}

	public function register_taxonomies() {
		register_taxonomy( 'video-channel', [ $this->post_type_name() ], [
			'public'       => false,
			'show_ui'      => true,
			'hierarchical' => true,
			'labels' => [
				'name' => __( 'Channel', 'tsvc' ),
			],
		] );
		register_taxonomy( 'video-source', [ $this->post_type_name() ], [
			'public'       => false,
			'show_ui'      => true,
			'hierarchical' => true,
			'labels' => [
				'name' => __( 'Source', 'tsvc' ),
			],
		] );
	}
}

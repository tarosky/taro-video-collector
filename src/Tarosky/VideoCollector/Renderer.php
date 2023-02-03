<?php

namespace Tarosky\VideoCollector;

use Tarosky\VideoCollector\Model\VideoPostType;
use Tarosky\VideoCollector\Pattern\SingletonPattern;

/**
 * Render YouTube video.
 */
class Renderer extends SingletonPattern {

	/**
	 * {@inheritdoc}
	 */
	protected function init() {
		// Register hooks.
		add_shortcode( 'videos', function( $attrs = [], $content = '' ) {
			ob_start();
			$this->video_list( $attrs );
			$rendered = ob_get_contents();
			ob_end_clean();
			return $rendered;
		} );
		// CSS.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueueu assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'tsvc-style', tsvc_root_directory_uri() . '/assets/css/tsvc.css', [], '1.0.0' );
	}

	/**
	 * Render video list according to arguments.
	 *
	 * @param array $args Arguments.
	 *
	 * @return void
	 */
	public function video_list( $args = [] ) {
		$args       = wp_parse_args( $args, [
			'number'  => 12,
			'page'    => 1,
			'order'   => 'date',
			'channel' => '',
			'wrapper' => 'video-list',
			'empty'   => '',
		] );
		$query_args = [
			'post_type'      => VideoPostType::get_instance()->post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $args['number'],
			'paged'          => max( 1, $args['page'] ),
		];
		switch ( $args['order'] ) {
			case 'view':
				$query_args['meta_key'] = '_video_view_count';
				$query_args['orderby']  = [
					'meta_value_num' => 'DESC',
				];
				break;
			case 'like':
				$query_args['meta_key'] = '_video_like_count';
				$query_args['orderby']  = [
					'meta_value_num' => 'DESC',
				];
				break;
			default:
				// Date.
				$query_args['orderby'] = [ 'date' => 'DESC' ];
				break;
		}
		// Add meta query.
		if ( ! empty( $args['channel'] ) ) {
			$channels  = array_filter( array_map( 'trim', explode( ',', $args['channel'] ) ) );
			$tax_query = [
				'relation' => 'OR',
			];
			foreach ( $channels as $channel ) {
				$channel = trim( $channel );
				$query   = [
					'taxonomy' => VideoPostType::get_instance()->taxonomy_channel,
				];
				if ( is_numeric( $channel ) ) {
					// This is term id.
					$query['field'] = 'term_id';
					$channel        = (int) $channel;
				} else {
					// This is term name.
					$query['field'] = 'name';
				}
				$query['terms'] = [ $channel ];
				$tax_query[]    = $query;
			}
			$query_args['tax_query'] = $tax_query;
		}
		$query = new \WP_Query( $query_args );
		if ( ! $query->have_posts() ) {
			// No post found.
			return;
		}
		printf( '<div class="%s">', esc_attr( $args['wrapper'] ) );
		do_action( 'tsvc_before_video_loop', $query );
		while ( $query->have_posts() ) {
			$query->the_post();
			tsvc_get_template_part( 'video-list' );
		}
		wp_reset_postdata();
		do_action( 'tsvc_after_video_loop', $query );
		echo '</div>';
	}
}

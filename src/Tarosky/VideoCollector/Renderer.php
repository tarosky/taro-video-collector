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
			$args = [];
			if ( $content ) {
				$args['empty'] = $content;
			}
			$this->video_list( array_merge( $args, $attrs ) );
			$rendered = ob_get_contents();
			ob_end_clean();
			return $rendered;
		} );
		// CSS.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		// If single page is active, add contents.
		if ( VideoPostType::get_instance()->public_flag() ) {
			add_filter( 'the_content', [ $this, 'content_filter' ] );
		}
		// Thumbnail filter.
		add_filter( 'post_thumbnail_html', [ $this, 'thumbnail_filter' ], 10, 5 );
		// Excerpt filter.
		add_filter( 'get_the_excerpt', [ $this, 'excerpt_filter' ], 10, 2 );
		// Change URL.
		add_filter( 'post_type_link', [ $this, 'permalink_filter' ], 10, 2 );
	}

	/**
	 * Enqueueu assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$hash = md5_file( tsvc_root_directory() . '/assets/css/tsvc.css' );
		wp_enqueue_style( 'tsvc-style', tsvc_root_directory_uri() . '/assets/css/tsvc.css', [], $hash );
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
			'number'      => 12,
			'page'        => 1,
			'order'       => 'date',
			'channel'     => '',
			'wrapper'     => 'video-list',
			'format'      => 'iframe',
			'thubmnail'   => 'standard',
			'empty'       => '',
			'width'       => 1920,
			'height'      => 1080,
			'title'       => '1',
			'date'        => '1',
			'publisher'   => '1',
			'icon'        => '1',
			'size'        => 'default',
			'date_format' => '',
			'offset'      => 0,
			'post__in'    => '',
			'prepend'     => '',
		] );
		$query_args = [
			'post_type'      => VideoPostType::get_instance()->post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $args['number'],
		];
		// set offset.
		if ( 0 < $args['offset'] ) {
			$query_args['offset'] = $args['offset'];
		} elseif ( 0 < $args['page'] ) {
			$query_args['paged'] = max( 1, $args['page'] );
		}
		// Specify post id.
		if ( ! empty( $args['post__in'] ) ) {
			$query_args['post__in'] = array_map( 'trim', explode( ',', $args['post__in'] ) );
		}
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
		// Results.
		$posts = [];
		// If front is set, prepend them.
		if ( ! empty( $args['include'] ) ) {
			$include_ids = array_map( function( $id ) {
				return (int) trim( $id );
			}, explode( ',', $args['include'] ) );
			if ( ! empty( $include_ids ) ) {
				$additional_args = [
					'post_type'   => VideoPostType::get_instance()->post_type,
					'post_status' => 'publish',
					'post__in'    => $include_ids,
					'orderby'     => 'post__in',
				];
				$pre_query       = new \WP_Query( $additional_args );
				if ( $pre_query->have_posts() ) {
					$posts += $pre_query->posts;
				}
				$query_args['post__not_in'] = $include_ids;
			}
		}
		// Do the query.
		$query = new \WP_Query( $query_args );
		if ( $query->have_posts() ) {
			// Offset if already retrieved.
			$offset = count( $posts );
			$limit  = $args['number'] - $offset;
			for ( $i = 0; $i < $limit; $i++ ) {
				$posts[] = $query->posts[ $i ];
			}
		}

		// Finally, posts empty?
		if ( empty( $posts ) ) {
			// No post found.
			if ( ! empty( $args['empty'] ) ) {
				echo wp_kses_post( $args['empty'] );
			}
			return;
		}
		printf( '<div class="%s">', esc_attr( $args['wrapper'] ) );
		do_action( 'tsvc_before_video_loop', $query, $args );
		global $post;
		foreach ( $posts as $post ) {
			setup_postdata( $post );
			tsvc_get_template_part( 'video-list', $args['format'], $args );
		}
		wp_reset_postdata();
		do_action( 'tsvc_after_video_loop', $query, $args );
		echo '</div>';
	}

	/**
	 * Add content.
	 *
	 * @return string
	 */
	public function content_filter( $content ) {
		if ( VideoPostType::get_instance()->post_type !== get_post_type() ) {
			return $content;
		}
		$flag = apply_filters( 'tsvc_automatic_contents', true, get_the_ID() );
		if ( ! $flag ) {
			// Explicitly denied by filter.
			return $flag;
		}
		ob_start();
		tsvc_get_template_part( 'content-video' );
		$addition = ob_get_contents();
		ob_end_clean();
		return $content . $addition;
	}

	/**
	 * Get string.
	 *
	 * @param string       $html
	 * @param int          $post_id
	 * @param int          $post_thumbnail_id
	 * @param string|int[] $size
	 * @param array        $attr
	 *
	 * @return string
	 */
	public function thumbnail_filter( $html, $post_id, $post_thumbnail_id = 0, $size = 'thumbnail', $attr = [] ) {
		if ( ! empty( $html ) ) {
			// Thumbnail is set.
			return $html;
		}
		if ( VideoPostType::get_instance()->post_type !== get_post_type( $post_id ) ) {
			return $html;
		}
		$video = get_post_meta( $post_id, '_video_info', true );
		if ( empty( $video['snippet']['thumbnails'] ) ) {
			return $html;
		}
		switch ( $size ) {
			case 'large':
			case 'full':
				$ratio = 'maxres';
				break;
			case 'post-thumbnail':
				$ratio = 'standard';
				break;
			default:
				$ratio = 'default';
				break;
		}
		$size = apply_filters( 'tsvc_video_thumbnail_size', $ratio, $post_id, $size, $attr );
		if ( empty( $video['snippet']['thumbnails'][ $ratio ] ) ) {
			$image = $video['snippet']['thumbnails']['default'];
		} else {
			$image = $video['snippet']['thumbnails'][ $ratio ];
		}
		$attr['loading'] = 'lazy';
		$attr['width']   = $image['width'];
		$attr['height']  = $image['height'];
		$attr['src']     = $image['url'];
		$attr['class']   = "attachment-{$size} ";
		$attrs           = [];
		foreach ( $attr as $key => $value ) {
			$attrs[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
		}
		return sprintf( '<img %s/>', implode( ' ', $attrs ) );
	}

	/**
	 * Trim excerpt.
	 *
	 * @param string   $excerpt Excerpt string.
	 * @param \WP_Post $post    Post object.
	 *
	 * @return string
	 */
	public function excerpt_filter( $excerpt, $post ) {
		if ( VideoPostType::get_instance()->post_type !== $post->post_type ) {
			return $excerpt;
		}
		// This is video post type.
		if ( is_single( $post->ID ) ) {
			return $excerpt;
		}
		$excerpt_length = (int) _x( '55', 'excerpt_length' );
		$excerpt_length = (int) apply_filters( 'excerpt_length', $excerpt_length );
		$excerpt_more   = apply_filters( 'excerpt_more', ' ' . '[&hellip;]' );
		return wp_trim_words( $excerpt, $excerpt_length, $excerpt_more );
	}

	/**
	 * Filter URL.
	 *
	 * @param string   $url  URL
	 * @param \WP_Post $post Post object.
	 *
	 * @return string
	 */
	public function permalink_filter( $url, $post ) {
		if ( is_admin() ) {
			return $url;
		}
		if ( VideoPostType::get_instance()->post_type !== $post->post_type ) {
			return $url;
		}
		if ( 2 > VideoPostType::get_instance()->public_flag() ) {
			return $url;
		}
		$youtube_url = VideoPostType::get_instance()->get_video_url( $post->ID );
		return $youtube_url ?: $url;
	}
}

<?php

namespace Tarosky\VideoCollector\Model;


use Tarosky\VideoCollector\Pattern\PostTypePattern;

/**
 * Video post type.
 */
class VideoPostType extends PostTypePattern {

	/**
	 * @var string Taxonomy name of Channel.
	 */
	public $taxonomy_channel = 'video-channel';

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
			'labels'            => [
				'name'          => __( 'Videos', 'tsvc' ),
				'singular_name' => __( 'Video', 'tsvc' ),
			],
			'public'            => false,
			'show_ui'           => true,
			'show_in_nav_menus' => false,
			'show_in_admin_bar' => false,
			'show_in_rest'      => false,
			'menu_icon'         => 'dashicons-video-alt3',
			'supports'          => [ 'title', 'excerpt', 'revisions' ],
		];
	}

	public function register_taxonomies() {
		register_taxonomy( $this->taxonomy_channel, [ $this->post_type_name() ], [
			'public'            => false,
			'show_ui'           => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'labels'            => [
				'name' => __( 'Channel', 'tsvc' ),
			],
		] );
		register_taxonomy( 'video-source', [ $this->post_type_name() ], [
			'public'            => false,
			'show_ui'           => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'labels'            => [
				'name' => __( 'Source', 'tsvc' ),
			],
		] );
	}

	/**
	 * Save video information.
	 *
	 * @param array $video   Video object.
	 * @param int   $post_id Post ID of video.
	 *
	 * @return int|\WP_Error  Post ID on success, WP_Error on failure.
	 */
	public function save_video( $video, $post_id = 0 ) {
		$args = [];
		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || $this->post_type_name() !== $post->post_type ) {
				return new \WP_Error( 'invalid_post_id', __( 'Specified post is not a video.', 'tsvc' ) );
			}
			$args['ID'] = $post_id;
		}
		$args['post_title']   = $video['snippet']['title'];
		$args['post_type']    = $this->post_type_name();
		$args['post_excerpt'] = $video['snippet']['description'];
		$date                 = new \DateTime( $video['snippet']['publishedAt'] );
		$args['post_date']    = $date->format( 'Y-m-d H:i:s' );
		$args['post_status']  = ( 'public' === $video['status']['privacyStatus'] ) ? 'publish' : 'private';
		$result               = wp_insert_post( $args, true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		// Save post meta.
		update_post_meta( $result, '_video_id', $video['id'] );
		update_post_meta( $result, '_video_info', $video );
		foreach ( [ 'view', 'like', 'dislike', 'favorite' ] as $key ) {
			$value_key = $key . 'Count';
			if ( isset( $video['statistics'][ $value_key ] ) ) {
				update_post_meta( $result, "_video_{$key}_count", $video['statistics'][ $value_key ] );
			}
		}
		// Save last synced.
		update_post_meta( $result, '_last_synced', current_time( 'mysql', true ) );
		// Update Channels.
		$channel = $this->get_channel( $video );
		if ( ! is_wp_error( $channel ) ) {
			wp_set_object_terms( $result, [ $channel->term_id ], $channel->taxonomy );
		}
		// Update Source
		wp_set_object_terms( $result, [ 'YouTube' ], 'video-source' );
		// Finish.
		return $result;
	}

	/**
	 * Get video id.
	 *
	 * @param string $video_id YouTube video ID.
	 * @return int Post id.
	 */
	public function video_exists( $video_id ) {
		$query = new \WP_Query( [
			'post_type'      => $this->post_type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_query'     => [
				[
					'key'   => '_video_id',
					'value' => $video_id,
				],
			],
		] );
		if ( $query->have_posts() ) {
			return $query->posts[0]->ID;
		};
		return 0;
	}

	/**
	 * Get channel from video information.
	 *
	 * @param array $video
	 *
	 * @return \WP_Term|\WP_Error
	 */
	public function get_channel( $video ) {
		$channel_id = $video['snippet']['channelId'] ?? null;
		if ( is_null( $channel_id ) ) {
			return new \WP_Error( 'invalid_video', __( 'Failed to get channel ', 'tsvc' ) );
		}
		// Get channel by meta key.
		$term_query = new \WP_Term_Query( [
			'taxonomy'   => $this->taxonomy_channel,
			'number'     => 1,
			'meta_query' => [
				[
					'key'   => '_channel_id',
					'value' => $channel_id,
				],
			],
		] );
		foreach ( $term_query->get_terms() as $term ) {
			return $term;
		}
		// No term found. Create one.
		$term = wp_insert_term( $video['snippet']['channelTitle'], $this->taxonomy_channel, [
			'slug' => strtolower( $channel_id ),
		] );
		if ( is_wp_error( $term ) ) {
			return $term;
		}
		update_term_meta( $term['term_id'], '_channel_id', $channel_id );
		return get_term( $term['term_id'], $this->taxonomy_channel );
	}

	/**
	 * Get video URL.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function get_video_url( $post_id ) {
		$id = get_post_meta( $post_id, '_video_id', true );
		if ( ! $id ) {
			return '';
		}
		return add_query_arg( [
			'v' => rawurlencode( $id ),
		], 'https://www.youtube.com/watch' );
	}

	/**
	 * Get Video iframe.
	 *
	 * @param int   $post_id Post id.
	 * @param array $options Option.
	 *
	 * @return string
	 */
	public function get_video_iframe( $post_id, $options = [] ) {
		$id = get_post_meta( $post_id, '_video_id', true );
		if ( ! $id ) {
			return '';
		}
		$src     = sprintf( 'https://www.youtube.com/embed/%s', $id );
		$options = wp_parse_args( $options, [
			'width'   => 640,
			'height'  => 360,
			'loading' => 'lazy',
		] );
		return sprintf(
			'<iframe type="text/html" width="%s" height="%s" loading="%s" src="%s" frameborder="0"></iframe>',
			esc_attr( $options['width'] ),
			esc_attr( $options['height'] ),
			esc_attr( $options['loading'] ),
			esc_url( $src )
		);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function meta_box() {
		// Video details.
		add_meta_box( 'youtube-info', __( 'Video Information', 'tsvc' ), function( \WP_Post $post ) {
			$url = $this->get_video_url( $post->ID );
			if ( ! $url ) {
				printf( '<p class="description">%s</p>', esc_html_e( 'Failed to find a valid video URL.', 'tsvc' ) );

				return;
			}
			?>
			<style>
				.video-wrapper iframe {
					width: 100%;
				}
				.tsvc-info-table {
					width: 100%;
					table-layout: fixed;
					border-collapse: collapse;
				}
				.tsvc-info-table th {

				}
				.tsvc-info-table td {
					text-align: right;
				}
				.tsvc-info-table th,
				.tsvc-info-table td {
					padding: 10px;
					border: 1px solid #ddd;
				}
			</style>
			<p class="video-wrapper">
				<?php echo $this->get_video_iframe( $post->ID ); ?>
			</p>
			<table class="tsvc-info-table">
				<thead>
				<tr>
					<th><?php esc_html_e( 'View', 'tsvc' ); ?></th>
					<th><?php esc_html_e( 'Like', 'tsvc' ); ?></th>
					<th><?php esc_html_e( 'Dislike', 'tsvc' ); ?></th>
					<th><?php esc_html_e( 'Favorite', 'tsvc' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<?php foreach ( [ 'view', 'like', 'dislike', 'favorite' ] as $key ) : ?>
					<td>
						<?php
						$value = get_post_meta( $post->ID, "_video_{$key}_count", true );
						if ( is_numeric( $value ) ) {
							echo number_format( $value );
						} else {
							echo '<span style="color: lightgray">---</span>';
						}
						?>
					</td>
					<?php endforeach; ?>
				</tr>
				</tbody>
			</table>
			<?php
		}, $this->post_type_name() );

		// Video raw data.
		add_meta_box( 'youtube-sync', __( 'Sync Information', 'tsvc' ), function( \WP_Post $post ) {
			?>
			<p>
				<label><?php esc_html_e( 'Last Synced', 'tsvc' ); ?></label><br />
				<input type="text" readonly
					value="<?php echo esc_attr( get_post_meta( $post->ID, '_last_synced', true ) ); ?>"
					placeholder="<?php esc_html_e( 'Unknown', 'tsvc' ); ?>" />
			</p>
			<p>
				<label><?php esc_html_e( 'Raw Data', 'tsvc' ); ?></label><br />
				<?php
				$raw_data =  json_encode( get_post_meta( $post->ID, '_video_info', true ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
				?>
				<textarea readonly rows="10" style="width: 100%; box-sizing: border-box"><?php echo esc_textarea( $raw_data ); ?></textarea>
			</p>
			<?php
		}, $this->post_type_name() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function posts_columns( $columns ) {
		$new_columns = [];
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'title' === $key ) {
				$new_columns['video'] = __( 'Video', 'tsvc' );
			}
		}
		return $new_columns;
	}

	/**
	 * {@inheritdoc}
	 */
	public function render_posts_column( $column, $post_id ) {
		switch ( $column ) {
			case 'video':
				$video = get_post_meta( $post_id, '_video_info', true );
				if ( empty( $video['snippet']['thumbnails']['medium'] ) ) {
					echo '<span style="color: lightgray">---</span>';
					return;
				}
				$image = $video['snippet']['thumbnails']['medium'];
				printf(
					'<a href="%s" title="%s" rel="noopener noreferrer" target="_blank"><img style="max-width: 120px; height: auto;" src="%s" loading="lazy" width="%d" height="%d" /></a>',
					esc_url( $this->get_video_url( $post_id ) ),
					esc_attr__( 'Open in YouTube', 'tsvc' ),
					esc_url( $image['url'] ),
					esc_attr( $image['width'] ),
					esc_attr( $image['height'] )
				);
				break;
		}
	}
}

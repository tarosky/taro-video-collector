<?php

namespace Tarosky\VideoCollector\Model;


use Tarosky\VideoCollector\Pattern\PostTypePattern;

/**
 * Post type to register Single
 */
class VideoConditions extends PostTypePattern {


	/**
	 * {@inheritdoc}
	 */
	protected function post_type_name() {
		return 'video-conditions';
	}

	/**
	 * {@inheritdoc}
	 */
	public function post_type_args() {
		return [
			'labels'            => [
				'name'          => __( 'Conditions', 'tsvc' ),
				'singular_name' => __( 'Condition', 'tsvc' ),
			],
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => 'edit.php?post_type=' . VideoPostType::get_instance()->post_type,
			'show_in_nav_menus' => false,
			'show_in_admin_bar' => false,
			'show_in_rest'      => false,
			'supports'          => [ 'title', 'excerpt' ],
		];
	}

	protected function update_post( $post ) {
		update_post_meta( $post->ID, '_interval', filter_input( INPUT_POST, '_interval' ) );
		update_post_meta( $post->ID, '_offset', filter_input( INPUT_POST, '_offset' ) );
		update_post_meta( $post->ID, '_channel_ids', filter_input( INPUT_POST, '_channel_ids' ) );
		update_post_meta( $post->ID, '_search_query', filter_input( INPUT_POST, '_search_query' ) );
	}

	protected function meta_box() {
		// Condition setting.
		add_meta_box( 'tsvc-condition-detail', __( 'Condition', 'tsvc' ), function( \WP_Post $post ) {
			$this->nonce_field();
			?>
			<p style="margin: 20px;">
				<label for="tsvc-interval"><?php esc_html_e( 'Update Frequency', 'tsvc' ); ?></label><br />
				<select id="tsvc-interval" name="_interval" style="width: 100%; box-sizing: border-box; margin: 10px 0;">
					<?php foreach ( [ 1, 2, 3, 4, 6, 8, 12, 24 ] as $frequency ) : ?>
						<option value="<?php echo esc_attr( $frequency ); ?>"<?php selected( $frequency, get_post_meta( $post->ID, '_interval', true ) ); ?>>
							<?php
							// translators: %d is hour.
							echo esc_html( sprintf( _n( 'Once per %d hour', 'Once per %d hours', $frequency, 'tsvc' ), $frequency ) );
							?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p style="margin: 20px;">
				<label for="tsvc-offset"><?php esc_html_e( 'Offset in Hour', 'tsvc' ); ?></label><br />
				<select id="tsvc-offset" name="_offset" style="width: 100%; box-sizing: border-box; margin: 10px 0;">
					<?php foreach ( range( 0, 12 ) as $offset ) : ?>
						<option value="<?php echo esc_attr( $offset ); ?>"<?php selected( $offset, (int) get_post_meta( $post->ID, '_offset', true ) ); ?>>
							<?php echo esc_html( $offset ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<span class="description">
					<?php esc_html_e( 'If you set 1 as offset and set every 6 hours, the condition runs at 1, 7, 13, and 19.', 'tsvc' ); ?>
				</span>
			</p>

			<p style="margin: 20px;">
				<label for="tsvc-channels"><?php esc_html_e( 'Channel ID', 'tsvc' ); ?></label><br />
				<textarea id="tsvc-channels" name="_channel_ids" rows="5" style="width: 100%; box-sizing: border-box; margin: 10px 0 5px;"
					><?php
						echo esc_textarea( get_post_meta( $post->ID, '_channel_ids', true ) );
					?></textarea>
				<span class="description"><?php esc_html_e( 'Enter YouTube channel IDs, 1 in each line.', 'tsvc' ); ?></span>
			</p>

			<p style="margin: 20px;">
				<label for="tsvc-search-query"><?php esc_html_e( 'Search Query', 'tsvc' ); ?></label><br />
				<textarea id="tsvc-search-query" name="_search_query" rows="5" style="width: 100%; box-sizing: border-box; margin: 10px 0 5px;"
					><?php
					echo esc_textarea( get_post_meta( $post->ID, '_search_query', true ) );
					?></textarea>
				<span class="description"><?php esc_html_e( 'Enter query in CSV format, 1 in each line. Words in 1 line are considered as AND search, Each lines are combined as OR search.', 'tsvc' ); ?></span>
			</p>
			<?php
		}, $this->post_type );

		// Get API results.
		add_meta_box( 'tsvc-condition-result', __( 'Validation', 'tsvc' ), function( \WP_Post $post ) {
			$channels = $this->get_post_channel_details( $post->ID );
			?>
			<h4><?php esc_html_e( 'Channel Result', 'tsvc' ); ?></h4>
			<?php if ( empty( $channels ) ) : ?>
				<p class="wp-ui-text-notification"><?php esc_html_e( 'No channel set.', 'tsvc' ); ?></p>
			<?php elseif ( is_wp_error( $channels ) ) : ?>
				<p class="wp-ui-text-notification"><?php echo esc_html( $channels->get_error_message() ); ?></p>
			<?php else : ?>
				<ol>
					<?php foreach ( $channels as $channel ) : ?>
					<li>
						<span><?php echo esc_html( $channel['snippet']['title'] ); ?></span>
						(<a href="<?php echo esc_url( sprintf( 'https://www.youtube.com/channel/%s/', $channel['id'] ) ); ?>" rel="noopener noreferrer" target="_blank">YouTube</a>)
						<code>#<?php echo esc_html( $channel['id'] ); ?></code>
					</li>
					<?php endforeach; ?>
				</ol>
				<?php
			endif;
			// Executing plan.
			$hours = $this->matching_hours( $post->ID );
			printf(
				'<p>%s <strong>%s</strong></p>',
				esc_html__( 'Sync every day at:', 'tsvc' ),
				implode( ', ', $hours )
			);
			// Sync result.
			$last_synced = get_post_meta( $post->ID, '_last_synced', true );
			printf(
				'<p>%s <strong style="%s">%s</strong>',
				esc_html__( 'Last Synced:', 'tsvc' ),
				( $last_synced ? '' : 'color: lightgray' ),
				( $last_synced ? esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_synced ) ) : '---' )
			);
		}, $this->post_type );
	}

	/**
	 * Get channel ids of post.
	 *
	 * @param int $post_id
	 * @return string[]
	 */
	public function get_channel_ids( $post_id ) {
		$post_meta = get_post_meta( $post_id, '_channel_ids', true );
		if ( empty( $post_meta ) ) {
			return [];
		}
		return array_values( array_filter( preg_split( '/(\r\n|\r|\n)/u', $post_meta ) ) );
	}

	/**
	 * Get post channel details.
	 *
	 * @param int $post_id
	 * @return array[]|\WP_Error
	 */
	public function get_post_channel_details( $post_id ) {
		$channel_ids = $this->get_channel_ids( $post_id );
		if ( empty( $channel_ids ) ) {
			return [];
		}
		// Channel is specified.
		$channel_ids = implode( ',', $channel_ids );
		$cache_key   = 'tsvc_' . md5( $channel_ids );
		$cache       = get_transient( $cache_key );
		if ( false !== $cache ) {
			return $cache;
		}
		// No cache found, try to retrieve.
		$result = tsvideo_channels( $channel_ids );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		set_transient( $cache_key, $result );

		return $result;
	}

	/**
	 * Get duration in seconds.
	 *
	 * @param string $duration ISO 8061 format string.
	 *
	 * @return int
	 */
	public function convert_video_duration_to_seconds( $duration ) {
		$seconds  = 0;
		$interval = new \DateInterval( $duration );
		foreach ( [
			'h' => 60 * 60,
			'i' => 60,
			's' => 1,
		] as $key => $multiplier ) {
			$seconds += $interval->{$key} * $multiplier;
		}
		return $seconds;
	}

	/**
	 * Is this short video?
	 *
	 * @param array $video Video object.
	 * @return bool
	 */
	public function is_short( $video ) {
		if ( 60 < $this->convert_video_duration_to_seconds( $video['contentDetails']['duration'] ) ) {
			// More than 60 seconds.
			return false;
		}
		if ( preg_match( '/#Shorts/ui', $video['snippet']['title'] ) || preg_match( '/#Shorts/ui', $video['snippet']['description'] ) ) {
			// Explicitly declared this is '#Shorts'
			return true;
		}
		return true;
	}

	/**
	 * Is video matches criteria?
	 *
	 * @param array $video   Video object.
	 * @param int   $post_id Post ID,
	 *
	 * @return bool
	 */
	public function is_matching( $video, $post_id ) {
		// Is this #shorts?
		$include_short = apply_filters( 'tsvc_include_shorts', false, $post_id );
		if ( ! $include_short && $this->is_short( $video ) ) {
			return false;
		}
		// Is this more than 60 seconds?
		// Split in line.
		$conditions = $this->get_post_condition( $post_id );
		if ( empty( $conditions ) ) {
			return true;
		}
		foreach ( $conditions as $line ) {
			$should_match = count( $line );
			$matched      = 0;
			foreach ( $line as $word ) {
				// Is title matches?
				if ( false !== strpos( $video['snippet']['title'], $word ) ) {
					$matched++;
					continue 1;
				}
				// Is description matches?
				if ( false !== strpos( $video['snippet']['description'], $word ) ) {
					$matched++;
					continue 1;
				}
			}
			if ( $should_match === $matched ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get video condition.
	 *
	 * @param int $post_id Post id of condition.
	 * @return string[][]
	 */
	public function get_post_condition( $post_id ) {
		return array_map( function( $line ) {
			return array_values( array_filter( array_map( 'trim', explode( ',', $line ) ) ) );
		}, array_filter( preg_split( '/(\r\n|\r|\n)/u', (string) get_post_meta( $post_id, '_search_query', true ) ) ) );
	}

	/**
	 * Get matching hours of condition in day.
	 *
	 * @param int $post_id Post ID of condition.
	 * @return int[]
	 */
	public function matching_hours( $post_id ) {
		$offset         = (int) get_post_meta( $post_id, '_offset', true );
		$frequency      = max( 1, (int) get_post_meta( $post_id, '_interval', true ) );
		$hour           = $offset;
		$matching_hours = [];
		while ( 24 > $hour ) {
			$matching_hours[] = $hour;
			$hour            += $frequency;
		}
		return $matching_hours;
	}

	/**
	 * Get videos and save.
	 *
	 * @param int $post_id Post ID.
	 * @param int 4page    Pagination.
	 * @return \WP_Post[]|\WP_Error
	 */
	public function sync( $post_id, $page = 1 ) {
		$videos = $this->get_condition_videos( $post_id, $page );
		if ( is_wp_error( $videos ) ) {
			return $videos;
		}
		$posts      = [];
		$errors     = new \WP_Error();
		$controller = VideoPostType::get_instance();
		foreach ( $videos as $video ) {
			$existing = $controller->video_exists( $video['id'] );
			$result   = $controller->save_video( $video, $existing );
			if ( is_wp_error( $result ) ) {
				$errors->add( $result->get_error_code(), $result->get_error_message() );
			} else {
				$posts[] = get_post( $result );
			}
		}
		update_post_meta( $post_id, '_last_synced', current_time( 'mysql' ) );
		return $errors->get_error_messages() ? $errors : $posts;
	}

	/**
	 * Get videos matching condition.
	 *
	 * @param int $post_id ID of Condition post.
	 *
	 * @return array[]|\WP_Error
	 */
	public function get_condition_videos( $post_id, $page = 1 ) {
		$post = get_post( $post_id );
		if ( ! $post || $this->post_type_name() !== $post->post_type ) {
			return new \WP_Error( 'invalid_post_type', __( 'Condition not found.', 'tsvc' ) );
		}
		$channel_ids = $this->get_channel_ids( $post_id );
		if ( empty( $channel_ids ) ) {
			return new \WP_Error( 'no_chanel_ids', __( 'Channel ID is empty.', 'tsvc' ) );
		}
		$conditions = $this->get_post_condition( $post_id );
		$videos     = [];
		$errors     = new \WP_Error();
		foreach ( $channel_ids as $channel_id ) {
			$found = tsvideo_search( $channel_id, '' );
			if ( is_wp_error( $found ) ) {
				$errors->add( $found->get_error_code(), $found->get_error_message() );
				continue 1;
			}
			if ( empty( $found ) ) {
				continue 1;
			}
			$video_ids = [];
			foreach ( $found as $f ) {
				$video_ids[] = $f['id']['videoId'];
			}
			$video_details = tsvideo_get( implode( ',', $video_ids ) );
			if ( is_wp_error( $video_details ) ) {
				$errors->add( $video_details->get_error_code(), $video_details->get_error_message() );
				continue 1;
			}
			foreach ( $video_details as $v ) {
				if ( $this->is_matching( $v, $post_id ) ) {
					$videos[] = $v;
				}
			}
		}
		return $errors->get_error_messages() ? $errors : $videos;
	}
}

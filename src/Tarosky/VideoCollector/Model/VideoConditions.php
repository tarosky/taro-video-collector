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
			'labels' => [
				'name' => __( 'Conditions', 'tsvc' ),
				'singular_name' => __( 'Condition', 'tsvc' ),
			],
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => 'edit.php?post_type=' . VideoPostType::get_instance()->post_type,
			'show_in_nav_menus' => false,
			'show_in_admin_bar' => false,
			'show_in_rest' => false,
			'supports' => [ 'title', 'excerpt' ],
		];
	}

	protected function update_post( $post ) {
		update_post_meta( $post->ID, '_interval', filter_input( INPUT_POST, '_interval' ) );
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
							echo esc_html( sprintf( _n('Once per %d hour', 'Once per %d hours', $frequency, 'tsvc'), $frequency ) );
							?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p style="margin: 20px;">
				<label for="tsvc-channels"><?php esc_html_e( 'Channel ID', 'tsvc' ); ?></label><br />
				<textarea id="tsvc-channels" name="_channel_ids" rows="5" style="width: 100%; box-sizing: border-box; margin: 10px 0 5px;"><?php
					echo esc_textarea( get_post_meta( $post->ID, '_channel_ids', true ) );
				?></textarea>
				<span class="description"><?php esc_html_e( 'Enter YouTube channel IDs, 1 in each line.', 'tsvc' ); ?></span>
			</p>

			<p style="margin: 20px;">
				<label for="tsvc-search-query"><?php esc_html_e( 'Search Query', 'tsvc' ); ?></label><br />
				<textarea id="tsvc-search-query" name="_search_query" rows="5" style="width: 100%; box-sizing: border-box; margin: 10px 0 5px;"><?php
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
						<span><?php echo esc_html( $channel['snippet']['title'] ) ?></span>
						(<a href="<?php echo esc_url( sprintf( 'https://www.youtube.com/channel/%s/', $channel['id'] ) ); ?>" rel="noopener noreferrer" target="_blank">YouTube</a>)
						<code>#<?php echo esc_html( $channel['id'] ); ?></code>
					</li>
					<?php endforeach; ?>
				</ol>
			<?php endif;
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
		$cache_key = 'tsvc_' . md5( $channel_ids );
		$cache = get_transient( $cache_key );
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
	 * Is video matches criteria?
	 *
	 * @param array $video   Video object.
	 * @param int   $post_id Post ID,
	 *
	 * @return void
	 */
	public function is_matching( $video, $post_id ) {
		return true;
	}
}

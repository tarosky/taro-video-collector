<?php
/**
 * Video in
 */

$channel = tsvc_channel_detail();
?>

<div class="video-list-item wp-block-video-item">
	<div class="video-list-video">
		<?php
		echo tsvc_iframe( [
			'width'   => 1920,
			'height'  => 1080,
			'loading' => 'lazy',
		] );
		?>
	</div>

	<div class="video-list-meta">
		<?php
		if ( ! empty( $channel['snippet']['thumbnails']['default'] ) ) {
			printf(
				'<img src="%s" width="%d" height="%d" alt="" class="video-list-icon" />',
				esc_url( $channel['snippet']['thumbnails']['default']['url'] ),
				$channel['snippet']['thumbnails']['default']['width'],
				$channel['snippet']['thumbnails']['default']['height']
			);
		}
		?>

		<div class="video-list-meta-info">
			<p class="video-list-title">
				<?php the_title(); ?>
			</p>

			<?php if ( ! empty( $channel['snippet']['title'] ) ) : ?>
				<span class="video-list-publisher">
					<?php echo esc_html( $channel['snippet']['title'] ); ?>
				</span>
			<?php endif; ?>

			<time class="video-list-published-at" datetime="<?php echo esc_attr( tsvc_video_published_at( 'raw' ) ); ?>">
				<?php echo esc_html( tsvc_video_published_at( get_option( 'date_format' ) ) ); ?>
			</time>

		</div>

	</div>

</div>

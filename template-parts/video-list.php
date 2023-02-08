<?php
/**
 * Display YouTube video.
 */

$args = wp_parse_args( $args ?? [], [
	'width'       => 1920,
	'height'      => 1080,
	'title'       => '1',
	'date'        => '1',
	'publisher'   => '1',
	'icon'        => '1',
	'size'        => 'default',
	'date_format' => '',
] );

$channel = tsvc_channel_detail();

?>

<div class="video-list-item">
	<div class="video-list-video">
		<?php
		echo tsvc_iframe( [
			'width'   => $args['width'],
			'height'  => $args['height'],
			'loading' => 'lazy',
		] );
		?>
	</div>

	<div class="video-list-meta">
		<?php
		if ( $args['icon'] && ! empty( $channel['snippet']['thumbnails'][ $args['size'] ] ) ) {
			printf(
				'<img src="%s" width="%d" height="%d" alt="" class="video-list-icon" />',
				esc_url( $channel['snippet']['thumbnails'][ $args['size'] ]['url'] ),
				$channel['snippet']['thumbnails'][ $args['size'] ]['width'],
				$channel['snippet']['thumbnails'][ $args['size'] ]['height']
			);
		}
		?>

		<div class="video-list-meta-info">
			<?php if ( $args['title'] ) : ?>
				<p class="video-list-title">
					<?php the_title(); ?>
				</p>
			<?php endif; ?>

			<?php if ( $args['publisher'] && $channel['snippet']['title'] ) : ?>
				<span class="video-list-publisher"><?php echo esc_html( $channel['snippet']['title'] ); ?></span>
			<?php endif; ?>


			<?php if ( $args['date'] ) : ?>
				<time class="video-list-published-at" datetime="<?php echo esc_attr( tsvc_video_published_at( 'raw' ) ); ?>">
					<?php echo esc_html( tsvc_video_published_at( $args['date_format'] ) ); ?>
				</time>
			<?php endif; ?>

		</div>

	</div>

</div>


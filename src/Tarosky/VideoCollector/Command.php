<?php

namespace Tarosky\VideoCollector;


use cli\Table;
use Tarosky\VideoCollector\Model\VideoConditions;

/**
 * Youtube API Utility.
 */
class Command extends \WP_CLI_Command {

	/**
	 * Get YouTube videos from condition.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : Post ID of condition.
	 *
	 * @synopsis <post_id>
	 * @param array $arg
	 * @return void
	 */
	public function condition( $args ) {
		list( $post_id ) = $args;
		$channel_ids = VideoConditions::get_instance()->get_channel_ids( $post_id );
		if ( empty( $channel_ids ) ) {
			\WP_CLI::error( 'Channel ID is empty.' );
		}
		\WP_CLI::line( sprintf( 'Retrieve % channels...', count( $channel_ids ) ) );
		$videos = [];
		foreach ( $channel_ids as $channel_id ) {
			\WP_CLI::line( sprintf( 'Getting %s...', $channel_id ) );
			$found = tsvideo_search( $channel_id, '' );
			if ( empty( $found ) || is_wp_error( $found ) ) {
				$message = is_wp_error( $found ) ? $found->get_error_message() : sprintf( 'No video found in %s.', $channel_id );
				\WP_CLI::warning( $message );
				continue;
			}
			$video_ids = [];
			foreach ( $found as $f ) {
				$video_ids[] = $f['id']['videoId'];
			}
			$video_ids = implode( ',', $video_ids );
			$video_details = tsvideo_get( $video_ids );
			if ( is_wp_error( $video_details ) ) {
				\WP_CLI::warning( $video_details->get_error_message() );
				continue;
			}
			foreach ( $video_details as $v ) {
				if ( VideoConditions::get_instance()->is_matching( $v, $post_id ) ) {
					$videos[] = $v;
				}
			}
		}

		$table = new Table();
		$table->setHeaders( [ '#', 'Title', 'URL', 'Date', 'Description' ] );
		foreach ( $videos as $i => $video ) {
			$table->addRow( [
				$i + 1,
				$video['snippet']['title'],
				sprintf( 'https://www.youtube.com/watch?v=%s', rawurlencode( $video['id'] ) ),
				$video['snippet']['publishedAt'],
				'---',
			] );
		}
		$table->display();
	}

	/**
	 * Get youtube videos.
	 *
	 *
	 * @return void
	 */
	public function videos() {
		$videos = tsvideo_search( '', '' );
		if ( empty( $videos ) ) {
			\WP_CLI::error( 'No video matches.' );
		}
		$video_ids = implode( ',', array_map( function( $video ) {
			return $video['id']['videoId'];
		}, $videos ) );
		$video_details = tsvideo_get( $video_ids );
		var_dump( $video_details );
		exit;
		$table = new Table();
		$table->setHeaders( [ 'Title', 'URL', 'Date', 'Description' ] );
		foreach ( $videos as $video ) {
			$table->addRow( [
				$video['snippet']['title'],
				sprintf( 'https://www.youtube.com/watch?v=%s', rawurlencode( $video['id']['videoId'] ) ),
				$video['snippet']['publishedAt'],
				$video['snippet']['description'] ?: '---',
			] );
		}
		$table->display();
	}

}

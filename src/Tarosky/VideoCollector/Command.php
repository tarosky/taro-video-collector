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
	 * @param array $arg Options.
	 * @return void
	 */
	public function condition( $args ) {
		list( $post_id ) = $args;
		$controller      = VideoConditions::get_instance();
		$channel_ids     = $controller->get_channel_ids( $post_id );
		if ( empty( $channel_ids ) ) {
			\WP_CLI::error( 'Channel ID is empty.' );
		}
		\WP_CLI::line( sprintf( 'Retrieve %d channels...', count( $channel_ids ) ) );
		$conditions = $controller->get_post_condition( $post_id );
		\WP_CLI::line( '' );
		\WP_CLI::line( '## Conditions' );
		if ( empty( $conditions ) ) {
			\WP_CLI::line( 'No condition.' );
		} else {
			echo implode( PHP_EOL . 'OR' . PHP_EOL, array_map( function( $words ) {
				return 'Including ' . implode( ' AND ', array_map( function( $word ) {
					return sprintf( '"%s"', esc_attr( $word ) );
				}, $words ) );
			}, $conditions ) );
		}
		\WP_CLI::line( '' );
		$videos = $controller->get_condition_videos( $post_id );
		if ( is_wp_error( $videos ) ) {
			\WP_CLI::error( $videos->get_error_message() );
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
	 * Get query to match.
	 *
	 * Display conditions list.
	 *
	 * @param array $args Command options.
	 * @return void
	 */
	public function conditions( $args ) {
		$condition = VideoConditions::get_instance();
		$query     = new \WP_Query( [
			'post_type'      => $condition->post_type,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => [ 'date' => 'asc' ],
		] );
		if ( ! $query->have_posts() ) {
			\WP_CLI::error( 'No condition found.' );
		}
		$table = new Table();
		$table->setHeaders( [ 'ID', 'Title', 'Frequency', 'Offset', 'Matches', 'Status' ] );
		foreach ( $query->posts as $post ) {
			$table->addRow( [
				$post->ID,
				get_the_title( $post ),
				get_post_meta( $post->ID, '_interval', true ),
				get_post_meta( $post->ID, '_offset', true ),
				implode( ', ', $condition->matching_hours( $post->ID ) ),
				$post->post_status,
			] );
		}
		$table->display();
	}

	/**
	 * save YouTube videos from condition.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : Post ID of condition.
	 *
	 * @synopsis <post_id>
	 *
	 * @param array $args Options.
	 * @return void
	 */
	public function retrieve( $args ) {
		list( $post_id ) = $args;
		$result          = VideoConditions::get_instance()->sync( $post_id );
		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}
		$table = new Table();
		$table->setHeaders( [ 'ID', 'Title', 'URL' ] );
		foreach ( $result as $post ) {
			$table->addRow( [
				$post->ID,
				get_the_title( $post ),
				get_permalink( $post ),
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
		$video_ids     = implode( ',', array_map( function( $video ) {
			return $video['id']['videoId'];
		}, $videos ) );
		$video_details = tsvideo_get( $video_ids );
		$table         = new Table();
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

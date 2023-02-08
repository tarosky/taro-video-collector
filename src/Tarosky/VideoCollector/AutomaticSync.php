<?php

namespace Tarosky\VideoCollector;


use Tarosky\VideoCollector\Model\VideoConditions;
use Tarosky\VideoCollector\Pattern\SingletonPattern;

/**
 * Automatic Sync
 *
 * @package tsvc
 */
class AutomaticSync extends SingletonPattern {

	const EVENT_NAME = 'tsvc_cront_retrieve_event';

	/**
	 * {@inheritdoc}
	 */
	protected function init() {
		add_action( 'init', [ $this, 'register_cron' ] );
		add_action( self::EVENT_NAME, [ $this, 'do_cron' ] );
	}

	/**
	 * Register cron.
	 *
	 * @return void
	 */
	public function register_cron() {
		if ( ! wp_next_scheduled( self::EVENT_NAME ) ) {
			wp_schedule_event( time(), 'hourly', self::EVENT_NAME );
		}
	}

	/**
	 * Do cron.
	 *
	 * @param int $page Pagination. If the amount of conditions are more than 100, use this.
	 * @return void
	 */
	public function do_cron( $page = 1 ) {
		$controller = VideoConditions::get_instance();
		// Get all conditions.
		$query = new \WP_Query( [
			'post_type'      => VideoConditions::get_instance()->post_type,
			'posts_per_page' => 100,
			'paged'          => max( 1, $page ),
			'post_status'    => 'publish',
			'orderby'        => [ 'date' => 'desc' ],
		] );
		$now   = (int) date_i18n( 'H' );
		foreach ( $query->posts as $post ) {
			$hours = $controller->matching_hours( $post->ID );
			// Filter only matches.
			if ( ! in_array( $now, $hours, true ) ) {
				continue;
			}
			// Do sync.
			$result = $controller->sync( $post->ID );
			if ( is_wp_error( $result ) ) {
				// Log error.
				error_log( sprintf( "Sync Errors: %d\t%s", $post->ID, implode( "\t", $result->get_error_messages() ) ) );
				continue;
			}
		}
	}
}

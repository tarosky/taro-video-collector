<?php

namespace Tarosky\VideoCollector\Admin;


use Tarosky\VideoCollector\Model\VideoPostType;
use Tarosky\VideoCollector\Pattern\SingletonPattern;

/**
 * Extends editor.
 */
class TermEditor extends SingletonPattern {

	/**
	 * Taxonomy name.
	 *
	 * @return string
	 */
	protected function taxonomy() {
		return VideoPostType::get_instance()->taxonomy_channel;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( $this->taxonomy() . '_edit_form_fields', [ $this, 'edit_form' ], 10, 2 );
		add_action( 'edit_' . $this->taxonomy(), [ $this, 'term_updated' ], 10, 2 );
	}

	/**
	 * Update channel.
	 *
	 * @param int $term_id Term ID
	 * @param int $tt_id   Term taxonomy ID.
	 *
	 * @return void
	 */
	public function term_updated( $term_id, $tt_id ) {
		if ( ! wp_verify_nonce( filter_input( INPUT_POST, '_tsvctermnonce' ), 'update_channel_meta' ) ) {
			return;
		}
		// Save channel id.
		$channel_id = filter_input( INPUT_POST, 'channel_id' );
		if ( empty( $channel_id ) ) {
			delete_term_meta( $term_id, '_channel_id' );
			delete_term_meta( $term_id, '_last_synced' );
			delete_term_meta( $term_id, '_channel_data' );
		} else {
			update_term_meta( $term_id, '_channel_id', $channel_id );
			$this->sync_channel( $term_id, $channel_id );
		}
	}

	/**
	 * Render tag form.
	 *
	 * @param \WP_Term $term     Term object.
	 * @param string   $taxonomy Taxonomy.
	 *
	 * @return void
	 */
	public function edit_form( $term, $taxonomy ) {
		?>
		<tr>
			<th><?php esc_html_e( 'Channel ID', 'tsvc' ); ?></th>
			<td>
				<?php wp_nonce_field( 'update_channel_meta', '_tsvctermnonce', false ); ?>
				<input class="regular-text" type="text" name="channel_id" value="<?php echo esc_attr( get_term_meta( $term->term_id, '_channel_id', true ) ); ?>" />
				<br />
				<span class="description"><?php esc_html_e( 'If you change channel ID after the automatic synchronization.', 'tsvc' ); ?></span>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Last Synced', 'tsvc' ); ?></th>
			<td>
				<input placeholder="---" class="regular-text" type="text" readonly value="<?php echo esc_attr( get_term_meta( $term->term_id, '_last_synced', true ) ); ?>" />
			</td>
		</tr>
		<tr>
			<th><?php  esc_html_e( 'Raw Data', 'tsvc' );?></th>
			<td>
				<?php
				$data = get_term_meta( $term->term_id, '_channel_data', true );
				if ( $data ) {
					$data = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
				}
				?>
				<textarea rows="5" style="width:100%; box-sizing: border-box;" readonly><?php echo esc_textarea( $data ); ?></textarea>
			</td>
		</tr>
		<?php
	}

	/**
	 * Sync channel ID.
	 *
	 * @param int    $term_id    Term id.
	 * @param string $channel_id Channel ID.
	 *
	 * @return true|\WP_Error
	 */
	public function sync_channel( $term_id, $channel_id ) {
		$channels = tsvideo_channels( $channel_id, [
			'maxResults' => 1,
		] );
		if ( is_wp_error( $channels ) ) {
			return $channels;
		}
		foreach ( $channels as $channel ) {
			update_term_meta( $term_id, '_channel_data', $channel );
			update_term_meta( $term_id, '_last_synced', current_time( 'mysql' ) );
		}
		return true;
	}


	public function sync_channels( $channel_ids ) {
		
	}
}

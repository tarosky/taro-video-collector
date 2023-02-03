<?php

namespace Tarosky\VideoCollector\Admin;


use Tarosky\VideoCollector\Model\VideoPostType;
use Tarosky\VideoCollector\Pattern\SingletonPattern;

/**
 * Register setting screen.
 */
class Settings extends SingletonPattern {

	/**
	 * {@inheritdoc}
	 */
	protected function init() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register_admin_setting' ] );
	}

	/**
	 * Register menu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_submenu_page( 'edit.php?post_type=' . VideoPostType::get_instance()->post_type, __( 'API Setting', 'tsvc' ), __( 'API Setting', 'tsvc' ), 'manage_options', 'tsvc-setting', [ $this, 'render_menu' ] );
	}

	/**
	 * Render setting menu.
	 *
	 * @return void
	 */
	public function render_menu() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'YouTube API Setting', 'tsvc' ); ?></h1>
			<form method="post" action="<?php echo admin_url( 'options.php' ); ?>">
				<?php
				settings_fields( 'tsvc-setting' );
				do_settings_sections( 'tsvc-setting' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Admin setting.
	 *
	 * @return void
	 */
	public function register_admin_setting() {
		// Add setting.
		add_settings_section( 'tsvc-api-setting', __( 'YouTube API', 'tsvc' ), function() {

		}, 'tsvc-setting' );
		// Add setting field.
		add_settings_field( 'tsvideo_youtube_api_key', __( 'API Key', 'tsvc' ), function() {
			?>
			<input class="regular-text" type="text" name="tsvideo_youtube_api_key" value="<?php echo esc_attr( get_option( 'tsvideo_youtube_api_key' ) ); ?>" />
			<?php
			if ( defined( 'YOUTUBE_API_KEY' ) ) {
				printf( '<p class="description">%s</p>', wp_kses_post( __( 'Constant <code>YOUTUBE_API_KEY</code> is defined, so it prior to the input field value above.', 'tsvc' ) ) );
			}
			?>
			<?php
		}, 'tsvc-setting', 'tsvc-api-setting' );
		register_setting( 'tsvc-setting', 'tsvideo_youtube_api_key' );
	}
}

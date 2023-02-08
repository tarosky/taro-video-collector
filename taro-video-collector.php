<?php
/*
Plugin Name: Taro Video Collector
Plugin URI:
Description: Collect specified video
Author: TAROSKY INC.
Author URI: https://tarosky.co.jp
Text Domain: tsvc
Domain Path: /languages/
License: GPL v3 or later.
Version: nightly
*/

// Do not access directly.
defined( 'ABSPATH' ) || die();

require_once __DIR__ . '/vendor/autoload.php';

// Initialize plugin.
add_action( 'plugins_loaded', function() {

	load_plugin_textdomain( 'tsvc', false, basename( __DIR__ ) . '/languages' );

	require_once __DIR__ . '/includes/api.php';
	require_once __DIR__ . '/includes/utilities.php';

	// Post type.
	\Tarosky\VideoCollector\Model\VideoPostType::get_instance();
	\Tarosky\VideoCollector\Model\VideoConditions::get_instance();
	// Term editor.
	\Tarosky\VideoCollector\Admin\TermEditor::get_instance();
	// Setting screen.
	\Tarosky\VideoCollector\Admin\Settings::get_instance();
	// Renderer.
	\Tarosky\VideoCollector\Renderer::get_instance();
	// Automatic sync
	\Tarosky\VideoCollector\AutomaticSync::get_instance();

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::add_command( 'youtube', \Tarosky\VideoCollector\Command::class );
	}
} );

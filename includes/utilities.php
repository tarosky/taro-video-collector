<?php
/**
 * Utility functions.
 *
 * @package tsvc
 */

/**
 * Get plugin root URI.
 *
 * @return string
 */
function tsvc_root_directory_uri() {
	return untrailingslashit( plugin_dir_url( __DIR__ ) );
}

/**
 * Load template including plugin template.
 *
 * @param string $slug   String template.
 * @param string $suffix Suffix if needed.
 * @param array  $args   Additional arguments.
 *
 * @return void
 */
function tsvc_get_template_part( $slug, $suffix = '', $args = [] ) {
	$files = [ $slug . '.php' ];
	if ( $suffix ) {
		array_unshift( $files, $slug . '-' . $suffix . '.php' );
	}
	// Set base directories.
	$base_dirs = [ get_stylesheet_directory() . '/template-parts/tsvc' ];
	if ( get_stylesheet_directory() !== get_template_directory() ) {
		// Parent theme exists, add.
		$base_dirs[] = get_template_directory() . '/template-parts/tsvc';
	}
	$base_dirs[] = dirname( __DIR__ ) . '/template-parts';
	$base_dirs   = apply_filters( 'tsvc_template_directories', $base_dirs );
	// Scan files.
	$found = '';
	foreach ( $files as $file ) {
		foreach ( $base_dirs as $dir ) {
			$path = $dir . '/' . ltrim( $file, '/' );
			if ( file_exists( $path ) ) {
				$found = $path;
				break 2;
			}
		}
	}
	$found = apply_filters( 'tsvc_template_file', $found, $slug, $suffix, $args );
	load_template( $found, false, $args );
}

/**
 * Get video iframe.
 *
 * @see \Tarosky\VideoCollector\Model\VideoPostType::get_video_iframe()
 *
 * @param array            $args Arugments.
 * @param int|null|WP_Post $post Post object.
 *
 * @return string
 */
function tsvc_iframe( $args = [], $post = null ) {
	$post = get_post( $post );
	return \Tarosky\VideoCollector\Model\VideoPostType::get_instance()->get_video_iframe( $post->ID, $args );
}

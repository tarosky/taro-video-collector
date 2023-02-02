<?php
/**
 * API functions.
 */


/**
 * APIキーを取得する
 *
 * @return string
 */
function tsvideo_get_api_key() {
	// 定数があれば使う
	// なければオプションから取る
	if ( defined( 'YOUTUBE_API_KEY' ) ) {
		$api_key = YOUTUBE_API_KEY;
	} else {
		$api_key = get_option( 'tsvideo_youtube_api_key' );
	}

	// フィルターをかけて返す
	return apply_filters( 'tsvideo_youtube_api_key', $api_key );
}

/**
 * Get API URL
 *
 * @param string $path API Path.
 * @param array  $args Query arguments.
 *
 * @return string
 */
function tsvideo_get_endpoint( $path, $args = [] ) {
	$endpoint = untrailingslashit( 'https://www.googleapis.com/youtube/v3/' . ltrim( $path, '/' ) );
	$args = array_merge( $args, [
		'key' => rawurlencode( tsvideo_get_api_key() ),
	] );
	return add_query_arg( $args, $endpoint );
}


function tsvideo_playlists( $channel_id ) {
	$endpoint = tsvideo_get_endpoint( 'playlists', [
		'channelId' => rawurlencode( $channel_id ),
		'part'      => 'id,snippet,status',
	] );
	$result = wp_remote_get( $endpoint );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	return json_decode( $result['body'], true );
}

/**
 * Search video in channel.
 *
 * @param string $channel_id YouTube Channel ID.
 * @param string $q          Query
 *
 * @return array|WP_Error
 */
function tsvideo_search( $channel_id, $q ) {
	$endpoint = tsvideo_get_endpoint( 'search', [
		'channelId'  => rawurlencode( $channel_id ),
		'part'       => 'id,snippet',
		'maxResults' => 50,
		'order'      => 'date',
		'q'          => rawurlencode( $q ),
	] );
	$result = wp_remote_get( $endpoint );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	$response = json_decode( $result['body'], true );
	return empty( $response['items'] ) ? [] : $response['items'];
}

/**
 * Get video detail information.
 *
 * @param string $id CSV format ids.
 *
 * @return array|WP_Error
 */
function tsvideo_get( $id ) {
	$endpoint = tsvideo_get_endpoint( 'videos', [
		'part'       => 'id,snippet,contentDetails,statistics,status',
		'order'      => 'date',
		'id'         => $id,
	] );
	$result = wp_remote_get( $endpoint );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	$response = json_decode( $result['body'], true );
	return empty( $response['items'] ) ? [] : $response['items'];
}

/**
 * Get channel information.
 *
 * @param string $channel_ids CSV list of channel ids.
 * @return array|WP_Error
 */
function tsvideo_channels( $channel_ids ) {
	$endpoint = tsvideo_get_endpoint( 'channels', [
		'part'       => 'id,snippet,contentDetails,statistics,brandingSettings',
		'maxResults' => 50,
		'id'         => $channel_ids,
	] );
	$result = wp_remote_get( $endpoint );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	$response = json_decode( $result['body'], true );
	return empty( $response['items'] ) ? [] : $response['items'];

}

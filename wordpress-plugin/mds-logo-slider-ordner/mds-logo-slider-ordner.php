<?php
/**
 * Plugin Name: MDS Logo-Slider Ordner
 * Description: Liefert die Bilder eines FileBird-Ordners für den Logo-Slider aus (nur lesend): GET /wp-json/mds-logo-slider/v1/ordner/<id>
 * Version: 1.0.0
 * Requires Plugins: filebird
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route( 'mds-logo-slider/v1', '/ordner/(?P<id>\d+)', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => 'mds_logo_slider_ordner',
	) );
} );

function mds_logo_slider_ordner( $request ) {
	global $wpdb;

	$ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT attachment_id FROM {$wpdb->prefix}fbv_attachment_folder WHERE folder_id = %d",
		(int) $request['id']
	) );

	$logos = array();
	foreach ( $ids as $id ) {
		if ( ! wp_attachment_is_image( $id ) ) {
			continue;
		}
		$url = wp_get_attachment_image_url( $id, 'medium_large' );
		if ( ! $url ) {
			continue;
		}
		// Alte Slider-Markierungen wie [logo-slider-2026] nicht an Screenreader weitergeben.
		$name = trim( preg_replace( '/\s*\[logo-slider[^\]]*\]/', '', (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) );
		if ( '' === $name ) {
			$name = get_the_title( $id );
		}
		$logos[] = array( 'name' => $name, 'url' => $url );
	}

	return rest_ensure_response( $logos );
}

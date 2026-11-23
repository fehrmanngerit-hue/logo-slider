<?php
/**
 * Plugin Name: MDS Logo-Slider Ordner
 * Description: Liefert die Bilder eines FileBird-Ordners für Logo-Slider und Bildergalerie aus (nur lesend): GET /wp-json/mds-logo-slider/v1/ordner/<id> oder /wp-json/mds-logo-slider/v1/ordner?pfad=Ordner/Unterordner
 * Version: 1.1.1
 * Requires Plugins: filebird
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route( 'mds-logo-slider/v1', '/ordner/(?P<id>\d+)', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => 'mds_logo_slider_ordner',
	) );
	register_rest_route( 'mds-logo-slider/v1', '/ordner', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => 'mds_logo_slider_ordner_pfad',
	) );
} );

// Ordnerpfad wie "MACH DICH STARK-Tage/2026/Logos" in die FileBird-Ordner-ID auflösen.
function mds_logo_slider_ordner_pfad( $request ) {
	global $wpdb;

	$teile  = array_filter( array_map( 'trim', preg_split( '/[\/›>]/u', (string) $request->get_param( 'pfad' ) ) ), 'strlen' );
	$ordner = 0;
	foreach ( $teile as $name ) {
		$ordner = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}fbv WHERE name = %s AND parent = %d AND type = 0 ORDER BY id LIMIT 1",
			$name,
			$ordner
		) );
		if ( ! $ordner ) {
			return new WP_Error( 'ordner_nicht_gefunden', 'FileBird-Ordner nicht gefunden: ' . $name, array( 'status' => 404 ) );
		}
	}
	if ( ! $ordner ) {
		return new WP_Error( 'pfad_fehlt', 'Parameter pfad fehlt', array( 'status' => 400 ) );
	}

	$request['id'] = $ordner;
	return mds_logo_slider_ordner( $request );
}

function mds_logo_slider_ordner( $request ) {
	global $wpdb;

	$ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT attachment_id FROM {$wpdb->prefix}fbv_attachment_folder WHERE folder_id = %d",
		(int) $request['id']
	) );
	if ( ! $ids ) {
		return rest_ensure_response( array() );
	}

	// Neueste zuerst, wie in der Mediathek.
	$bilder = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_mime_type' => 'image',
		'post__in'       => array_map( 'intval', $ids ),
		'orderby'        => 'date',
		'order'          => 'DESC',
		'posts_per_page' => -1,
	) );

	$logos = array();
	foreach ( $bilder as $bild ) {
		$id  = $bild->ID;
		$url = wp_get_attachment_image_url( $id, 'medium_large' );
		if ( ! $url ) {
			continue;
		}
		// Alte Markierungen wie [logo-slider-2026] nicht an Screenreader weitergeben.
		$name = trim( preg_replace( '/\s*\[[a-z0-9-]+\]/', '', (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) );
		if ( '' === $name ) {
			// get_the_title() liefert Entities wie &#8211; – als Klartext ausgeben.
			$name = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
		}
		$logos[] = array(
			'name'  => $name,
			'url'   => $url,
			'large' => wp_get_attachment_image_url( $id, 'large' ),
			'full'  => wp_get_attachment_image_url( $id, 'full' ),
			'thumb' => wp_get_attachment_image_url( $id, 'thumbnail' ),
		);
	}

	return rest_ensure_response( $logos );
}

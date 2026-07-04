<?php
/**
 * Uninstall handler for Rivian Accessory Guide.
 *
 * Removes all plugin data when the plugin is deleted via the WordPress admin.
 *
 * @package Rivian_Accessory_Guide
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Delete all accessory posts and their meta.
$posts = get_posts( array(
	'post_type'      => 'rivian_accessory',
	'posts_per_page' => -1,
	'post_status'    => 'any',
	'fields'         => 'ids',
) );

foreach ( $posts as $post_id ) {
	wp_delete_post( $post_id, true );
}

// Delete taxonomy terms.
$terms = get_terms( array(
	'taxonomy'   => 'rivian_accessory_category',
	'hide_empty' => false,
	'fields'     => 'ids',
) );

if ( ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term_id ) {
		wp_delete_term( $term_id, 'rivian_accessory_category' );
	}
}

$vehicle_terms = get_terms( array(
	'taxonomy'   => 'rivian_accessory_vehicle',
	'hide_empty' => false,
	'fields'     => 'ids',
) );

if ( ! is_wp_error( $vehicle_terms ) ) {
	foreach ( $vehicle_terms as $term_id ) {
		wp_delete_term( $term_id, 'rivian_accessory_vehicle' );
	}
}

// Delete plugin options.
delete_option( 'rag_version' );

// Flush rewrite rules.
flush_rewrite_rules();

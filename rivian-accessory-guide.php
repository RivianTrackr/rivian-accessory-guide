<?php
/**
 * Plugin Name: Rivian Accessory Guide
 * Description: A curated accessory guide for Rivian vehicles with dark-themed card layout and affiliate links.
 * Version: 1.0.0
 * Author: Jose Castillo
 * Text Domain: rivian-accessory-guide
 * License: GPL-2.0-or-later
 *
 * @package Rivian_Accessory_Guide
 */

defined( 'ABSPATH' ) || exit;

define( 'RAG_VERSION', '1.0.0' );
define( 'RAG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RAG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoload plugin classes.
 */
spl_autoload_register( function ( $class ) {
	$prefix = 'RAG_';
	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}
	$relative = substr( $class, strlen( $prefix ) );
	$file     = RAG_PLUGIN_DIR . 'includes/class-rag-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

/**
 * Initialize the plugin.
 */
function rag_init() {
	RAG_Post_Type::register();
	RAG_Shortcode::register();
}
add_action( 'init', 'rag_init' );

/**
 * Register the meta box.
 */
function rag_add_meta_boxes() {
	RAG_Meta_Box::add();
}
add_action( 'add_meta_boxes', 'rag_add_meta_boxes' );

/**
 * Save meta box data.
 */
function rag_save_post( $post_id ) {
	RAG_Meta_Box::save( $post_id );
}
add_action( 'save_post_rivian_accessory', 'rag_save_post' );

/**
 * Enqueue frontend assets only when the shortcode is present.
 */
function rag_enqueue_frontend_assets() {
	if ( RAG_Shortcode::$enqueued ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style(
			'rag-frontend',
			RAG_PLUGIN_URL . 'frontend/css/rag-frontend' . $suffix . '.css',
			array(),
			RAG_VERSION
		);
		wp_enqueue_script(
			'rag-frontend',
			RAG_PLUGIN_URL . 'frontend/js/rag-frontend' . $suffix . '.js',
			array(),
			RAG_VERSION,
			true
		);
	}
}
add_action( 'wp_footer', 'rag_enqueue_frontend_assets' );

/**
 * Enqueue admin assets on accessory edit screens.
 */
function rag_enqueue_admin_assets( $hook ) {
	$screen = get_current_screen();
	if ( $screen && 'rivian_accessory' === $screen->post_type ) {
		wp_enqueue_style(
			'rag-admin',
			RAG_PLUGIN_URL . 'admin/css/rag-admin.css',
			array(),
			RAG_VERSION
		);
	}
}
add_action( 'admin_enqueue_scripts', 'rag_enqueue_admin_assets' );

/**
 * Flush rewrite rules on activation.
 */
function rag_activate() {
	RAG_Post_Type::register();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'rag_activate' );

/**
 * Flush rewrite rules on deactivation.
 */
function rag_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'rag_deactivate' );

<?php
/**
 * Plugin Name: Rivian Accessory Guide
 * Description: A curated accessory guide for Rivian vehicles with dark-themed card layout and affiliate links.
 * Version: 1.0.0
 * Author: Jose Castillo
 * Text Domain: rivian-accessory-guide
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'RAG_VERSION', '1.0.0' );
define( 'RAG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RAG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once RAG_PLUGIN_DIR . 'includes/class-rag-post-type.php';
require_once RAG_PLUGIN_DIR . 'includes/class-rag-meta-box.php';
require_once RAG_PLUGIN_DIR . 'includes/class-rag-shortcode.php';

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
        wp_enqueue_style(
            'rag-frontend',
            RAG_PLUGIN_URL . 'assets/css/rag-frontend.css',
            array(),
            RAG_VERSION
        );
        wp_enqueue_script(
            'rag-frontend',
            RAG_PLUGIN_URL . 'assets/js/rag-frontend.js',
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
            RAG_PLUGIN_URL . 'assets/css/rag-admin.css',
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

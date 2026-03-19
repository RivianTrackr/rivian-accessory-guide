<?php
/**
 * Custom admin panel for the Rivian Accessory Guide.
 *
 * @package Rivian_Accessory_Guide
 */

defined( 'ABSPATH' ) || exit;

class RAG_Admin {

	/**
	 * Boot the admin panel.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
	}

	/**
	 * Register the top-level admin menu and submenus.
	 */
	public function register_menu() {
		add_menu_page(
			'Accessory Guide',
			'Accessories',
			'manage_options',
			'rag-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-car',
			30
		);

		add_submenu_page(
			'rag-dashboard',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'rag-dashboard',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'rag-dashboard',
			'All Accessories',
			'All Accessories',
			'manage_options',
			'rag-accessories',
			array( $this, 'render_list' )
		);

		add_submenu_page(
			'rag-dashboard',
			'Add New Accessory',
			'Add New',
			'manage_options',
			'rag-accessory-edit',
			array( $this, 'render_edit' )
		);

		// Hide the default CPT menu.
		remove_menu_page( 'edit.php?post_type=rivian_accessory' );
	}

	/**
	 * Enqueue admin CSS and JS on our pages only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'rag-' ) === false ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style(
			'rag-admin',
			RAG_PLUGIN_URL . 'admin/css/rag-admin.css',
			array(),
			RAG_VERSION
		);

		wp_enqueue_script(
			'rag-admin',
			RAG_PLUGIN_URL . 'admin/js/rag-admin' . $suffix . '.js',
			array( 'jquery' ),
			RAG_VERSION,
			true
		);

		wp_enqueue_media();
	}

	/**
	 * Route admin actions.
	 */
	public function handle_actions() {
		if ( isset( $_POST['rag_accessory_save'] ) ) {
			$this->handle_save();
		}

		if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['post_id'] ) ) {
			$this->handle_delete();
		}

		if ( isset( $_POST['rag_bulk_action'] ) && 'delete' === $_POST['rag_bulk_action'] ) {
			$this->handle_bulk_delete();
		}
	}

	// --- Page Renderers ---

	/**
	 * Render the dashboard page.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once RAG_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render the accessory list page.
	 */
	public function render_list() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once RAG_PLUGIN_DIR . 'admin/views/accessory-list.php';
	}

	/**
	 * Render the add/edit page.
	 */
	public function render_edit() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once RAG_PLUGIN_DIR . 'admin/views/accessory-edit.php';
	}

	// --- Action Handlers ---

	/**
	 * Save an accessory (create or update).
	 */
	private function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		check_admin_referer( 'rag_accessory_save', 'rag_accessory_nonce' );

		$editing_id = isset( $_POST['editing_id'] ) ? intval( $_POST['editing_id'] ) : 0;
		$post       = wp_unslash( $_POST );

		$post_data = array(
			'post_type'   => 'rivian_accessory',
			'post_status' => 'publish',
			'post_title'  => sanitize_text_field( $post['accessory_title'] ?? '' ),
			'post_content' => wp_kses_post( $post['accessory_description'] ?? '' ),
			'menu_order'  => intval( $post['menu_order'] ?? 0 ),
		);

		if ( $editing_id > 0 ) {
			$post_data['ID'] = $editing_id;
			$result = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			wp_redirect( admin_url( 'admin.php?page=rag-accessories&message=error' ) );
			exit;
		}

		$post_id = is_int( $result ) ? $result : $editing_id;

		// Save buy link.
		$buy_link = esc_url_raw( $post['buy_link'] ?? '' );
		if ( $buy_link ) {
			update_post_meta( $post_id, '_rag_buy_link', $buy_link );
		} else {
			delete_post_meta( $post_id, '_rag_buy_link' );
		}

		// Save category.
		$category_id = intval( $post['accessory_category'] ?? 0 );
		if ( $category_id > 0 ) {
			wp_set_object_terms( $post_id, $category_id, 'rivian_accessory_category' );
		} else {
			wp_set_object_terms( $post_id, array(), 'rivian_accessory_category' );
		}

		// Save featured image.
		$thumbnail_id = intval( $post['featured_image_id'] ?? 0 );
		if ( $thumbnail_id > 0 ) {
			set_post_thumbnail( $post_id, $thumbnail_id );
		} else {
			delete_post_thumbnail( $post_id );
		}

		$msg = $editing_id > 0 ? 'updated' : 'added';
		wp_redirect( admin_url( 'admin.php?page=rag-accessories&message=' . $msg ) );
		exit;
	}

	/**
	 * Delete a single accessory.
	 */
	private function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$post_id = intval( $_GET['post_id'] );
		check_admin_referer( 'rag_delete_' . $post_id );

		wp_delete_post( $post_id, true );
		wp_redirect( admin_url( 'admin.php?page=rag-accessories&message=deleted' ) );
		exit;
	}

	/**
	 * Bulk delete accessories.
	 */
	private function handle_bulk_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		check_admin_referer( 'rag_bulk_action', 'rag_bulk_nonce' );

		$post_ids = array_map( 'intval', $_POST['post_ids'] ?? array() );
		foreach ( $post_ids as $post_id ) {
			if ( $post_id > 0 ) {
				wp_delete_post( $post_id, true );
			}
		}

		wp_redirect( admin_url( 'admin.php?page=rag-accessories&message=bulk_deleted' ) );
		exit;
	}
}

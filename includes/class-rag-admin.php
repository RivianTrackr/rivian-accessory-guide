<?php
/**
 * Custom admin panel for the Rivian Accessory Guide.
 *
 * @package Rivian_Accessory_Guide
 */

defined( 'ABSPATH' ) || exit;

class RAG_Admin {

	/**
	 * Hook suffixes returned by add_menu_page / add_submenu_page.
	 *
	 * @var string[]
	 */
	private $page_hooks = array();

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
		$this->page_hooks[] = add_menu_page(
			'Accessory Guide',
			'Accessories',
			'manage_options',
			'rag-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-car',
			30
		);

		$this->page_hooks[] = add_submenu_page(
			'rag-dashboard',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'rag-dashboard',
			array( $this, 'render_dashboard' )
		);

		$this->page_hooks[] = add_submenu_page(
			'rag-dashboard',
			'All Accessories',
			'All Accessories',
			'manage_options',
			'rag-accessories',
			array( $this, 'render_list' )
		);

		$this->page_hooks[] = add_submenu_page(
			'rag-dashboard',
			'Add New Accessory',
			'Add New',
			'manage_options',
			'rag-accessory-edit',
			array( $this, 'render_edit' )
		);

		$this->page_hooks[] = add_submenu_page(
			'rag-dashboard',
			'Categories',
			'Categories',
			'manage_options',
			'rag-categories',
			array( $this, 'render_categories' )
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
		// Match by page slug (most reliable) or hook suffix (fallback).
		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$our_pages = array( 'rag-dashboard', 'rag-accessories', 'rag-accessory-edit', 'rag-categories' );

		if ( ! in_array( $page, $our_pages, true ) && ! in_array( $hook, $this->page_hooks, true ) ) {
			return;
		}

		$css_file = RAG_PLUGIN_DIR . 'admin/css/rag-admin.css';
		$css_ver  = file_exists( $css_file ) ? filemtime( $css_file ) : RAG_VERSION;

		wp_enqueue_style(
			'rag-admin',
			RAG_PLUGIN_URL . 'admin/css/rag-admin.css',
			array(),
			$css_ver
		);

		// Also load WP dashicons for empty-state icons.
		wp_enqueue_style( 'dashicons' );

		$js_file   = RAG_PLUGIN_DIR . 'admin/js/rag-admin.min.js';
		$js_suffix = file_exists( $js_file ) ? '.min' : '';
		$js_path   = RAG_PLUGIN_DIR . 'admin/js/rag-admin' . $js_suffix . '.js';
		$js_ver    = file_exists( $js_path ) ? filemtime( $js_path ) : RAG_VERSION;

		wp_enqueue_script(
			'rag-admin',
			RAG_PLUGIN_URL . 'admin/js/rag-admin' . $js_suffix . '.js',
			array( 'jquery' ),
			$js_ver,
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

		if ( isset( $_POST['rag_category_save'] ) ) {
			$this->handle_category_save();
		}

		if ( isset( $_GET['action'] ) && 'delete_category' === $_GET['action'] && isset( $_GET['term_id'] ) ) {
			$this->handle_category_delete();
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

	/**
	 * Render the categories management page.
	 */
	public function render_categories() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once RAG_PLUGIN_DIR . 'admin/views/categories.php';
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

	/**
	 * Create or update a category.
	 */
	private function handle_category_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		check_admin_referer( 'rag_category_save', 'rag_category_nonce' );

		$post       = wp_unslash( $_POST );
		$editing_id = intval( $post['editing_id'] ?? 0 );
		$name       = sanitize_text_field( $post['category_name'] ?? '' );
		$slug       = sanitize_title( $post['category_slug'] ?? '' );
		$description = sanitize_textarea_field( $post['category_description'] ?? '' );

		if ( empty( $name ) ) {
			wp_redirect( admin_url( 'admin.php?page=rag-categories&message=error' ) );
			exit;
		}

		$args = array( 'description' => $description );
		if ( $slug ) {
			$args['slug'] = $slug;
		}

		if ( $editing_id > 0 ) {
			$args['name'] = $name;
			$result = wp_update_term( $editing_id, 'rivian_accessory_category', $args );
			$msg    = 'updated';
		} else {
			$result = wp_insert_term( $name, 'rivian_accessory_category', $args );
			$msg    = 'added';
		}

		if ( is_wp_error( $result ) ) {
			wp_redirect( admin_url( 'admin.php?page=rag-categories&message=error' ) );
			exit;
		}

		wp_redirect( admin_url( 'admin.php?page=rag-categories&message=' . $msg ) );
		exit;
	}

	/**
	 * Delete a category.
	 */
	private function handle_category_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$term_id = intval( $_GET['term_id'] );
		check_admin_referer( 'rag_delete_category_' . $term_id );

		wp_delete_term( $term_id, 'rivian_accessory_category' );
		wp_redirect( admin_url( 'admin.php?page=rag-categories&message=deleted' ) );
		exit;
	}
}

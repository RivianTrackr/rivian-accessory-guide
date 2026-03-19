<?php
/**
 * Custom Post Type and Taxonomy registration.
 */

defined( 'ABSPATH' ) || exit;

class RAG_Post_Type {

    /**
     * Register the custom post type and taxonomy.
     */
    public static function register() {
        self::register_taxonomy();
        self::register_post_type();
    }

    /**
     * Register the rivian_accessory_category taxonomy.
     */
    private static function register_taxonomy() {
        $labels = array(
            'name'              => 'Categories',
            'singular_name'     => 'Category',
            'search_items'      => 'Search Categories',
            'all_items'         => 'All Categories',
            'parent_item'       => 'Parent Category',
            'parent_item_colon' => 'Parent Category:',
            'edit_item'         => 'Edit Category',
            'update_item'       => 'Update Category',
            'add_new_item'      => 'Add New Category',
            'new_item_name'     => 'New Category Name',
            'menu_name'         => 'Categories',
        );

        register_taxonomy( 'rivian_accessory_category', 'rivian_accessory', array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'accessory-category' ),
        ) );
    }

    /**
     * Register the rivian_accessory post type.
     */
    private static function register_post_type() {
        $labels = array(
            'name'               => 'Accessories',
            'singular_name'      => 'Accessory',
            'menu_name'          => 'Accessories',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Accessory',
            'edit_item'          => 'Edit Accessory',
            'new_item'           => 'New Accessory',
            'view_item'          => 'View Accessory',
            'search_items'       => 'Search Accessories',
            'not_found'          => 'No accessories found',
            'not_found_in_trash' => 'No accessories found in Trash',
        );

        register_post_type( 'rivian_accessory', array(
            'labels'       => $labels,
            'public'       => true,
            'has_archive'  => false,
            'show_in_rest' => true,
            'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
            'menu_icon'    => 'dashicons-car',
            'rewrite'      => array( 'slug' => 'accessory' ),
        ) );
    }
}

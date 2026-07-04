<?php
/**
 * Custom Post Type and Taxonomy registration.
 *
 * @package Rivian_Accessory_Guide
 */

defined( 'ABSPATH' ) || exit;

class RAG_Post_Type {

    /**
     * Register the custom post type and taxonomy.
     */
    public static function register() {
        self::register_taxonomy();
        self::register_vehicle_taxonomy();
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
            'labels'             => $labels,
            'hierarchical'       => true,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'rewrite'            => false,
        ) );
    }

    /**
     * Register the rivian_accessory_vehicle taxonomy.
     *
     * Non-hierarchical so a single accessory can be assigned to several
     * vehicles (e.g. fits both the R1T and R1S).
     */
    private static function register_vehicle_taxonomy() {
        $labels = array(
            'name'              => 'Vehicles',
            'singular_name'     => 'Vehicle',
            'search_items'      => 'Search Vehicles',
            'all_items'         => 'All Vehicles',
            'edit_item'         => 'Edit Vehicle',
            'update_item'       => 'Update Vehicle',
            'add_new_item'      => 'Add New Vehicle',
            'new_item_name'     => 'New Vehicle Name',
            'menu_name'         => 'Vehicles',
        );

        register_taxonomy( 'rivian_accessory_vehicle', 'rivian_accessory', array(
            'labels'             => $labels,
            'hierarchical'       => false,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'rewrite'            => false,
        ) );
    }

    /**
     * Register the rivian_accessory post type.
     *
     * Deliberately non-public: accessories only render inside the
     * [rivian_accessories] shortcode, and cards link straight to affiliate
     * URLs. Public single pages would be orphaned thin content in the
     * sitemap, so no permalinks, archives, or REST exposure.
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
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'show_ui'             => false,
            'has_archive'         => false,
            'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
            'rewrite'             => false,
        ) );
    }
}

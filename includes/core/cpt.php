<?php
if (!defined('ABSPATH')) exit;

/**
 * Register Custom Post Types: property, void, repair
 */
add_action('init', function () {
    // PROPERTY
    register_post_type('ppm_property', [
        'labels' => [
          'name'               => 'Properties',
          'singular_name'      => 'Property',
          'menu_name'          => 'Properties',
          'add_new'            => 'Add Property',
          'add_new_item'       => 'Add New Property',
          'edit_item'          => 'Edit Property',
          'new_item'           => 'New Property',
          'view_item'          => 'View Property',
          'search_items'       => 'Search Properties',
          'not_found'          => 'No properties found',
          'not_found_in_trash' => 'No properties found in Trash',
          'all_items'          => 'All Properties',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-building',
        'supports' => ['thumbnail', 'comments'],

      	// Custom caps (prefixed to avoid collisions)
        'capability_type' => ['ppm_property', 'ppm_properties'],
        'map_meta_cap'    => true,

      	// Ensures the menu is shown to users who can edit this CPT
    	'capabilities' => [
        	'edit_posts' => 'edit_ppm_properties',
    	],
    ]);

    // VOID (handover cycle)
    register_post_type('ppm_void', [
        'labels' => [
          'name'               => 'Voids',
          'singular_name'      => 'Void',
          'menu_name'          => 'Voids',
          'add_new'            => 'Add Void',
          'add_new_item'       => 'Add New Void',
          'edit_item'          => 'Edit Void',
          'new_item'           => 'New Void',
          'view_item'          => 'View Void',
          'search_items'       => 'Search Voids',
          'not_found'          => 'No voids found',
          'not_found_in_trash' => 'No voids found in Trash',
          'all_items'          => 'All Voids',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-clipboard',
        'supports' => ['thumbnail', 'comments'],

      	// Custom caps (prefixed to avoid collisions)
        'capability_type' => ['ppm_void', 'ppm_voids'],
        'map_meta_cap'    => true,

      	// Ensures the menu is shown to users who can edit this CPT
    	'capabilities' => [
        	'edit_posts' => 'edit_ppm_voids',
    	],
    ]);

    // REPAIR (work order)
    register_post_type('ppm_repair', [
        'labels' => [
          'name'               => 'Repairs',
          'singular_name'      => 'Repair',
          'menu_name'          => 'Repairs',
          'add_new'            => 'Add Repair',
          'add_new_item'       => 'Add New Repair',
          'edit_item'          => 'Edit Repair',
          'new_item'           => 'New Repair',
          'view_item'          => 'View Repair',
          'search_items'       => 'Search Repairs',
          'not_found'          => 'No repairs found',
          'not_found_in_trash' => 'No repairs found in Trash',
          'all_items'          => 'All Repairs',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-hammer',
        'supports' => ['thumbnail', 'comments'],

        // Custom caps (prefixed to avoid collisions)
        'capability_type' => ['ppm_repair', 'ppm_repairs'],
        'map_meta_cap'    => true,

      	// Ensures the menu is shown to users who can edit this CPT
    	'capabilities' => [
        	'edit_posts' => 'edit_ppm_repairs',
    	],
    ]);
});
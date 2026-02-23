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

    // VOID (Handover Cycle)
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

    // REPAIR (Work Order)
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

    // TENANT (Person/Family)
    add_action('init', function () {
      register_post_type('ppm_tenant', [
        'labels' => [
          'name'               => 'Tenants',
          'singular_name'      => 'Tenant',
          'menu_name'          => 'Tenants',
          'add_new'            => 'Add Tenant',
          'add_new_item'       => 'Add New Tenant',
          'edit_item'          => 'Edit Tenant',
          'new_item'           => 'New Tenant',
          'view_item'          => 'View Tenant',
          'search_items'       => 'Search Tenants',
          'not_found'          => 'No tenant found',
          'not_found_in_trash' => 'No tenant found in Trash',
          'all_items'          => 'All Tenants',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-businessperson',
        'supports' => ['thumbnail', 'comments'],
      ]);

      // TENANCY (Property occumation and repair history)
      register_post_type('ppm_tenancy', [
        'labels' => [
          'name'               => 'Tenancies',
          'singular_name'      => 'Tenancy',
          'menu_name'          => 'Tenancies',
          'add_new'            => 'Add Tenancy',
          'add_new_item'       => 'Add New Tenancy',
          'edit_item'          => 'Edit Tenancy',
          'new_item'           => 'New Tenancy',
          'view_item'          => 'View Tenancy',
          'search_items'       => 'Search Tenancies',
          'not_found'          => 'No tenancy found',
          'not_found_in_trash' => 'No tenancy found in Trash',
          'all_items'          => 'All Tenancies',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-clipboard',
        'supports' => ['thumbnail', 'comments'],
      ]);
    });
});
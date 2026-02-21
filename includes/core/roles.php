<?php
if (!defined('ABSPATH')) exit;

/**
 * Ensure core capabilities exist for Admin + Staff (basic for now).
 * We'll refine permissions/scope in Step 4.
 */
add_action('init', function () {
    $admin = get_role('administrator');
  	if ($admin && !$admin->has_cap('edit_ppm_properties')) {
        $admin->add_cap('edit_ppm_properties');
        $admin->add_cap('read_ppm_property');
        $admin->add_cap('publish_ppm_properties');
    }
  	if ($admin && !$admin->has_cap('edit_ppm_voids')) {
        $admin->add_cap('edit_ppm_voids');
        $admin->add_cap('read_ppm_void');
        $admin->add_cap('publish_ppm_voids');
    }
    if ($admin && !$admin->has_cap('edit_ppm_repairs')) {
        $admin->add_cap('edit_ppm_repairs');
        $admin->add_cap('read_ppm_repair');
        $admin->add_cap('publish_ppm_repairs');
    }
}, 30);

add_action('init', function () {

    // Admins already have manage_options; we leave them alone.

    $staff = get_role('staff');
    if ($staff) {
        // Basic read access to the CPTs (admin UI testing phase)
        $caps = [
            // Property
            //'edit_others_ppm_properties', (REMOVE IN PROD)
            'read_ppm_property', 'read_private_ppm_properties',
            'edit_ppm_property', 'edit_ppm_properties', 'edit_private_ppm_properties',
            'publish_ppm_properties', 'edit_published_ppm_properties',

            // Property Deletes (REMOVE IN PROD)
            //'delete_others_ppm_properties',
            //'delete_published_ppm_properties',
            //'delete_ppm_properties',
            //'delete_private_ppm_properties',

            // Void
            //'edit_others_ppm_voids', (REMOVE IN PROD)
            'read_ppm_void', 'read_private_ppm_voids',
            'edit_ppm_void', 'edit_ppm_voids', 'edit_private_ppm_voids',
            'publish_ppm_voids', 'edit_published_ppm_voids',

            // Void Deletes (REMOVE IN PROD)
            //'delete_others_ppm_voids',
            //'delete_published_ppm_voids',
            //'delete_ppm_voids',
            //'delete_private_ppm_voids',

            // Repair
            //'edit_others_ppm_repairs', (REMOVE IN PROD)
            'read_ppm_repair', 'read_private_ppm_repairs',
            'edit_ppm_repair', 'edit_ppm_repairs', 'edit_private_ppm_repairs',
            'publish_ppm_repairs', 'edit_published_ppm_repairs',

            // Repair Deletes (REMOVE IN PROD)
            //'delete_others_ppm_repairs',
            //'delete_published_ppm_repairs',
            //'delete_ppm_repairs',
            //'delete_private_ppm_repairs',

            // Comments on repairs
            'moderate_comments',
        ];

        foreach ($caps as $cap) {
            $staff->add_cap($cap);
        }
    }
}, 20);

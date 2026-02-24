<?php
if (!defined('ABSPATH')) exit;

// guard so staff can’t access wp-admin edit screens for these CPTs
add_action('current_screen', function ($screen) {
    if (!is_admin() || empty($screen->post_type)) return;

    $restricted = ['ppm_property', 'ppm_void', 'ppm_repair', 'ppm_tenant', 'ppm_tenancy'];

    if (in_array($screen->post_type, $restricted, true) && !current_user_can('manage_options')) {
        wp_safe_redirect(home_url('/property-management/'));
        exit;
    }
});

// (Model B visibility – later)
<?php
if (!defined('ABSPATH')) exit;

/**
 * Ensure ACF front-end forms work on portal form pages.
 * Must run before any output.
 */
add_action('wp', function () {
    if (!function_exists('acf_form_head')) return;

    // Never run during AJAX requests
    if (wp_doing_ajax()) return;

    // Only for logged-in staff
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) return;

    $slugs = [
        'add-property',
        'edit-property',
        'add-repair',
        'edit-repair',
        'add-void',
        'edit-void',
    ];

    if (is_page($slugs)) {
        acf_form_head();
    }
}, 0);

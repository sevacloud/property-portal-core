<?php
if (!defined('ABSPATH')) exit;

/**
 * Redirect logged-in staff away from the login page.
 */
add_action('template_redirect', function () {
    if (wp_doing_ajax()) return;
    if (is_page('staff-login') && is_user_logged_in() && ppc_is_staff_user()) {
        wp_safe_redirect(home_url('/property-management/'));
        exit;
    }
});

/**
 * Protect staff portal pages.
 * For now, we only protect the main dashboard page.
 * Later we’ll protect /property-management/* children too.
 */
add_action('template_redirect', function () {
    if (!is_page('property-management')) return;
    if (wp_doing_ajax()) return;

    if (!is_user_logged_in()) {
        // Send them to staff login, then back.
        $redirect_to = home_url('/property-management/');
        wp_safe_redirect(add_query_arg('redirect_to', rawurlencode($redirect_to), home_url('/staff-login/')));
        exit;
    }

    if (!ppc_is_staff_user()) {
        // Logged in but not staff/admin.
        wp_die('Access denied.');
    }
});

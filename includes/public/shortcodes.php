<?php
if (!defined('ABSPATH')) exit;

/**
 * Staff login shortcode.
 */
add_shortcode('ppc_staff_login', function () {

    if (is_user_logged_in() && ppc_is_staff_user()) {
        return '<div class="ppc-app"><div class="ppc-card"><p>You are already logged in.</p>
                <p><a href="' . esc_url(home_url('/property-management/')) . '">Go to Property Management</a></p></div></div>';
    }

    ob_start();

    $redirect = isset($_GET['redirect_to'])
        ? esc_url_raw(wp_unslash($_GET['redirect_to']))
        : home_url('/property-management/');

    echo '<div class="ppc-app"><div class="ppc-card">';
    
    wp_login_form([
        'redirect'       => $redirect,
        'remember'       => true,
        'label_username' => 'Email or Username',
        'label_password' => 'Password',
        'label_remember' => 'Remember me',
        'label_log_in'   => 'Sign in',
    ]);

    echo '<p><a href="' . esc_url(wp_lostpassword_url()) . '">Forgot password?</a></p>';
    echo '</div></div>';

    return ob_get_clean();
});
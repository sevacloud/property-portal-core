<?php
if (!defined('ABSPATH')) exit;

/**
 * Helpers: identify staff users.
 * We treat Administrators as staff too.
 */
function ppc_is_staff_user($user_id = null): bool {
    $user = $user_id ? get_user_by('id', $user_id) : wp_get_current_user();
    if (!$user || empty($user->ID)) return false;

    // Admins always allowed.
    if (user_can($user, 'manage_options')) return true;

    // Staff role allowed.
    return in_array('staff', (array) $user->roles, true);
}

/**
 * Sync ACF fields to post_title for Property, Repair, and Void.
 */
add_action('acf/save_post', function ($post_id) {

    // Ignore autosaves/revisions
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    $post_type = get_post_type($post_id);
    if (!$post_type) return;

    static $running = false;
    if ($running) return;
    $running = true;

    $title = '';

    /*
     |--------------------------------------------------------------------------
     | PROPERTY
     |--------------------------------------------------------------------------
     */
    if ($post_type === 'ppm_property') {

        $line1    = trim((string) get_field('property_address_line_1', $post_id));
        $town     = trim((string) get_field('property_town', $post_id));
        $postcode = strtoupper(trim((string) get_field('property_postcode', $post_id)));

        if ($line1 && $town && $postcode) {
            $title = "{$line1}, {$town} – {$postcode}";
        }
    }

    /*
     |--------------------------------------------------------------------------
     | REPAIR
     |--------------------------------------------------------------------------
     */
    if ($post_type === 'ppm_repair') {

        $summary  = trim((string) get_field('repair_summary', $post_id));
        $property = get_field('repair_property', $post_id);

        if ($summary && $property && !empty($property->post_title)) {
            $title = "{$property->post_title} – {$summary}";
        } elseif ($summary) {
            $title = $summary;
        }
    }

    /*
     |--------------------------------------------------------------------------
     | VOID
     |--------------------------------------------------------------------------
     */
    if ($post_type === 'ppm_void') {

        $property   = get_field('void_property', $post_id);
        $start_date = get_field('void_start_date', $post_id);

        if ($property && !empty($property->post_title)) {

            if ($start_date) {
                $formatted_date = date('Y-m-d', strtotime($start_date));
                $title = "{$property->post_title} – Void – {$formatted_date}";
            } else {
                $title = "{$property->post_title} – Void";
            }
        }
    }

    /*
     |--------------------------------------------------------------------------
     | TENANT
     |--------------------------------------------------------------------------
     */
    if ($post_type === 'ppm_tenant') {

        $name   = get_field('tenant_name', $post_id);
        $tenant_phone = get_field('tenant_phone', $post_id);

        if ($name) {

            if ($tenant_phone) {
                $title = "{$name} – {$tenant_phone}";
            } else {
                $title = "{$name}";
            }
        }
    }

    /*
     |--------------------------------------------------------------------------
     | TENANCY
     |--------------------------------------------------------------------------
     */
    if ($post_type === 'ppm_tenancy') {

        $property   = get_field('tenancy_property', $post_id);
        $tenant = get_field('tenancy_tenant', $post_id);

        if ($property && !empty($property->post_title)) {

            if ($tenant) {
                $title = "{$property->post_title} – Add Tenant Name/Phone";
            } else {
                $title = "{$property->post_title} – Add Tenant Name/Phone (zzz)";
            }
        }
    }

    if ($title) {
        wp_update_post([
            'ID'         => $post_id,
            'post_title' => sanitize_text_field($title),
        ]);
    }

    $running = false;

}, 20);

/**
 * Shared helpers (safe to define once).
 */
function ppc_fmt_date($ymd): string {
    if (!$ymd) return '';
    $ts = strtotime((string) $ymd);
    if (!$ts) return '';
    return date('d M Y', $ts);
}

function ppc_get_property_title_from_field(string $field_name, int $post_id): string {
    $prop = function_exists('get_field') ? get_field($field_name, $post_id) : null;
    if (is_object($prop) && !empty($prop->post_title)) return (string) $prop->post_title;
    if (is_numeric($prop) && (int)$prop > 0) return get_the_title((int)$prop) ?: '';
    return '';
}

function ppc_get_owner_name_from_field(string $field_name, int $post_id): string {
    $owner = function_exists('get_field') ? get_field($field_name, $post_id) : null;

    if (is_array($owner) && !empty($owner['display_name'])) return (string) $owner['display_name'];
    if (is_object($owner) && !empty($owner->display_name)) return (string) $owner->display_name;

    if (is_numeric($owner) && (int)$owner > 0) {
        $u = get_user_by('id', (int)$owner);
        return $u ? (string) $u->display_name : '';
    }
    return '';
}

/**
 * Find the current (active) tenancy for a property.
 * "Current" means tenancy_end is empty / not set.
 */
function ppc_get_current_tenancy_id_for_property(int $property_id): int {
    if ($property_id <= 0) return 0;

    $tenancies = get_posts([
        'post_type'      => 'ppm_tenancy',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'meta_value',
        'meta_key'       => 'tenancy_start',
        'order'          => 'DESC',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'tenancy_property',
                'value'   => (string) $property_id,
                'compare' => '=',
            ],
            [
                'relation' => 'OR',
                [
                    'key'     => 'tenancy_end',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => 'tenancy_end',
                    'value'   => '',
                    'compare' => '=',
                ],
            ],
        ],
    ]);

    return !empty($tenancies) ? (int) $tenancies[0]->ID : 0;
}

/**
 * Build a secure "End tenancy" URL.
 */
function ppc_end_tenancy_url(int $tenancy_id, string $redirect_to = ''): string {
    $redirect_to = $redirect_to ?: wp_get_referer();
    $url = add_query_arg([
        'action'     => 'ppc_end_tenancy',
        'tenancy_id' => $tenancy_id,
        'redirect_to'=> rawurlencode($redirect_to ?: home_url('/')),
    ], admin_url('admin-post.php'));

    return wp_nonce_url($url, 'ppc_end_tenancy_' . $tenancy_id);
}

/**
 * One-click End Tenancy handler (sets tenancy_end = today).
 */
add_action('admin_post_ppc_end_tenancy', function () {
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) {
        wp_die('Access denied.');
    }

    $tenancy_id = isset($_GET['tenancy_id']) ? (int) $_GET['tenancy_id'] : 0;
    if (!$tenancy_id || get_post_type($tenancy_id) !== 'ppm_tenancy') {
        wp_die('Invalid tenancy.');
    }

    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'ppc_end_tenancy_' . $tenancy_id)) {
        wp_die('Invalid end tenancy request.');
    }

    $end = function_exists('get_field') ? get_field('tenancy_end', $tenancy_id) : get_post_meta($tenancy_id, 'tenancy_end', true);

    // If already ended, just redirect
    if ($end) {
        $redirect_to = isset($_GET['redirect_to']) ? rawurldecode((string)$_GET['redirect_to']) : (wp_get_referer() ?: home_url('/'));
        wp_safe_redirect($redirect_to);
        exit;
    }

    $today = current_time('Y-m-d'); // uses WP site timezone

    if (function_exists('update_field')) {
        update_field('tenancy_end', $today, $tenancy_id);
    } else {
        update_post_meta($tenancy_id, 'tenancy_end', $today);
    }

    $redirect_to = isset($_GET['redirect_to']) ? rawurldecode((string)$_GET['redirect_to']) : (wp_get_referer() ?: home_url('/'));
    wp_safe_redirect($redirect_to);
    exit;
});

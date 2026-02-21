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

    if ($title) {
        wp_update_post([
            'ID'         => $post_id,
            'post_title' => sanitize_text_field($title),
        ]);
    }

    $running = false;

}, 20);
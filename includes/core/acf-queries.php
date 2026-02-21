<?php
if (!defined('ABSPATH')) exit;

/**
 * Force ACF Post Object queries to work reliably on the front-end by bypassing
 * any theme/plugin query filters (pre_get_posts/posts_where/etc).
 *
 * Key idea: suppress_filters => true
 */

function ppc_force_acf_post_object_args(array $args, array $post_types): array {
    $args['post_type'] = $post_types;

    // Ensure we’re querying what we expect
    $args['post_status'] = ['publish'];

    // Avoid paging surprises
    $args['posts_per_page'] = 200;
    $args['orderby'] = 'title';
    $args['order'] = 'ASC';

    // CRITICAL: bypass pre_get_posts / posts_where filters
    $args['suppress_filters'] = true;

    // Also remove this if present
    if (isset($args['perm'])) {
        unset($args['perm']);
    }

    return $args;
}

// Void -> Property
add_filter('acf/fields/post_object/query/key=field_ppc_void_property', function ($args, $field, $post_id) {
    return ppc_force_acf_post_object_args($args, ['ppm_property']);
}, 10, 3);

// Repair -> Property
add_filter('acf/fields/post_object/query/key=field_ppc_repair_property', function ($args, $field, $post_id) {
    return ppc_force_acf_post_object_args($args, ['ppm_property']);
}, 10, 3);

// Repair -> Void
add_filter('acf/fields/post_object/query/key=field_ppc_repair_void', function ($args, $field, $post_id) {
    return ppc_force_acf_post_object_args($args, ['ppm_void']);
}, 10, 3);
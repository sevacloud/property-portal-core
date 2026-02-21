<?php
if (!defined('ABSPATH')) exit;

/**
 * Integrity rule:
 * If a Repair links to a Void, the Repair's Property must match the Void's Property.
 *
 * This runs for wp-admin edits and also later for front-end ACF forms.
 */
add_action('acf/validate_save_post', function () {

    // Only validate when saving a Repair
    $post_id = isset($_POST['post_id']) ? sanitize_text_field(wp_unslash($_POST['post_id'])) : '';
    if (!$post_id || !is_numeric($post_id)) return;

    $post_id = (int) $post_id;
    if (get_post_type($post_id) !== 'ppm_repair') return;

    // ACF values come through POST under $_POST['acf'][field_key]
    if (empty($_POST['acf']) || !is_array($_POST['acf'])) return;

    $acf = $_POST['acf'];

    // Find the selected Repair Property and Repair Void from the posted ACF fields.
    // We look for our field keys (from field-groups.php).
    $repair_property_id = isset($acf['field_ppc_repair_property']) ? (int) $acf['field_ppc_repair_property'] : 0;
    $repair_void_id     = isset($acf['field_ppc_repair_void']) ? (int) $acf['field_ppc_repair_void'] : 0;

    if (!$repair_void_id) return; // no void linked, OK

    $void_property = get_field('void_property', $repair_void_id);
    $void_property_id = is_object($void_property) ? (int) $void_property->ID : (int) $void_property;

    if ($void_property_id && $repair_property_id && $void_property_id !== $repair_property_id) {
        acf_add_validation_error('field_ppc_repair_void', 'This void belongs to a different property. Please select the matching property or choose the correct void.');
    }
});
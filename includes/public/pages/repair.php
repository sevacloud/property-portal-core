<?php
if (!defined('ABSPATH')) exit;

/**
 * REPAIR details page
 * Usage:
 *   Dynamic repair ID fetched from URL param
 *   [ppc_repair]
 *
 *   Static repair page using ID
 *   [ppc_repair id="123"]
 */
add_shortcode('ppc_repair', function ($atts) {
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) {
        return '<p>Access denied.</p>';
    }

    $atts = shortcode_atts(['id' => 0, 'shell' => '1'], $atts);

    // Standalone usage should keep the portal shell/sidebar.
    // Internal route rendering uses shell="0" to avoid recursion.
    $use_shell = !in_array(strtolower((string) $atts['shell']), ['0', 'false', 'no'], true);
    if ($use_shell && empty($atts['id'])) {
        return do_shortcode('[ppc_portal_layout content="repair"]');
    }

    $repair_id = (int) $atts['id'];
    if ($repair_id <= 0 && isset($_GET['id'])) {
        $repair_id = (int) $_GET['id'];
    }

    if ($repair_id <= 0 || get_post_type($repair_id) !== 'ppm_repair') {
        return '<p>Invalid repair.</p>';
    }

    $get = function (string $name) use ($repair_id) {
        return function_exists('get_field') ? get_field($name, $repair_id) : get_post_meta($repair_id, $name, true);
    };

    $summary = (string) $get('repair_summary');
    $description = (string) $get('repair_description');
    $property_id = (int) $get('repair_property');
    $owner = $get('repair_owner');
    $priority = (string) $get('repair_priority');
    $status = (string) $get('repair_status');
    $due_date = $get('repair_due_date');
    $completion_date = $get('repair_completion_date');
    $cost = (string) $get('repair_cost');
    $contractor = (string) $get('repair_contractor');

    // Get owner display name
    $owner_name = '';
    if (is_array($owner) && !empty($owner['display_name'])) $owner_name = (string) $owner['display_name'];
    if (is_object($owner) && !empty($owner->display_name)) $owner_name = (string) $owner->display_name;
    if (is_numeric($owner) && (int) $owner > 0) {
        $u = get_user_by('id', (int) $owner);
        if ($u && !empty($u->display_name)) $owner_name = (string) $u->display_name;
    }

    // Get property title
    $property_title = $property_id ? (get_the_title($property_id) ?: '') : '';

    ob_start(); ?>
    <div class="ppc-stack">
        <header class="ppc-property-header">
            <div class="ppc-property-header__top">
                <h1 class="ppc-h1"><?php echo esc_html($summary ?: (get_the_title($repair_id) ?: 'Repair')); ?></h1>

                <div class="ppc-actions ppc-property-header__actions">
                    <a class="ppc-btn ppc-btn--compact" href="<?php echo esc_url(ppc_edit_url('repair', $repair_id)); ?>">Edit Repair</a>
                    
                    <?php if ($property_id > 0): ?>
                        <a class="ppc-btn ppc-btn--compact" href="<?php echo esc_url(ppc_property_page_url($property_id)); ?>">View Property</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="ppc-muted">Repair details and information.</div>
        </header>

        <section class="ppc-card">
            <h2 class="ppc-h2">Repair Details</h2>
            <table class="ppc-property-details-table">
                <tbody>
                    <?php if ($property_title): ?>
                        <tr>
                            <th>Property</th>
                            <td>
                                <a class="ppc-link" href="<?php echo esc_url(ppc_property_page_url($property_id)); ?>">
                                    <?php echo esc_html($property_title); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Summary</th>
                        <td><?php echo esc_html($summary ?: '-'); ?></td>
                    </tr>
                    <?php if ($description): ?>
                        <tr>
                            <th>Description</th>
                            <td><?php echo esc_html($description); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Owner</th>
                        <td><?php echo esc_html($owner_name ?: 'Unassigned'); ?></td>
                    </tr>
                    <tr>
                        <th>Priority</th>
                        <td><?php echo esc_html($priority ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><?php echo esc_html($status ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Due Date</th>
                        <td><?php echo esc_html(ppc_fmt_date($due_date) ?: '-'); ?></td>
                    </tr>
                    <?php if ($completion_date): ?>
                        <tr>
                            <th>Completion Date</th>
                            <td><?php echo esc_html(ppc_fmt_date($completion_date)); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($cost): ?>
                        <tr>
                            <th>Cost</th>
                            <td><?php echo esc_html($cost); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($contractor): ?>
                        <tr>
                            <th>Contractor</th>
                            <td><?php echo esc_html($contractor); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Created</th>
                        <td><?php echo esc_html(date('d M Y', strtotime(get_the_date('Y-m-d', $repair_id)))); ?></td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
    <?php
    return ob_get_clean();
});
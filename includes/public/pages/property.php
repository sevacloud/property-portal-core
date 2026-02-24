<?php
if (!defined('ABSPATH')) exit;

/**
 * PROPERTY details page
 * Usage:
 *   Dynamic property ID fetched from URL param
 *   [ppc_property]
 *
 *   Static property page using ID
 *   [ppc_property id="123"]
 */
add_shortcode('ppc_property', function ($atts) {
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) {
        return '<p>Access denied.</p>';
    }

    $atts = shortcode_atts(['id' => 0, 'shell' => '1'], $atts);

    // Standalone usage should keep the portal shell/sidebar.
    // Internal route rendering uses shell="0" to avoid recursion.
    $use_shell = !in_array(strtolower((string) $atts['shell']), ['0', 'false', 'no'], true);
    if ($use_shell && empty($atts['id'])) {
        return do_shortcode('[ppc_portal_layout content="property"]');
    }

    $property_id = (int) $atts['id'];
    if ($property_id <= 0 && isset($_GET['id'])) {
        $property_id = (int) $_GET['id'];
    }

    if ($property_id <= 0 || get_post_type($property_id) !== 'ppm_property') {
        return '<p>Invalid property.</p>';
    }

    $get = function (string $name) use ($property_id) {
        return function_exists('get_field') ? get_field($name, $property_id) : get_post_meta($property_id, $name, true);
    };

    $line1 = (string) $get('property_address_line_1');
    $line2 = (string) $get('property_address_line_2');
    $town = (string) $get('property_town');
    $county = (string) $get('property_county');
    $postcode = (string) $get('property_postcode');
    $code = (string) $get('property_code');
    $region = (string) $get('property_region');
    $status = (string) $get('property_status');
    $notes = (string) $get('property_notes');
    $main_photo = $get('property_main_photo');
    $manager = $get('property_manager');

    $manager_name = '';
    if (is_array($manager) && !empty($manager['display_name'])) $manager_name = (string) $manager['display_name'];
    if (is_object($manager) && !empty($manager->display_name)) $manager_name = (string) $manager->display_name;
    if (is_numeric($manager) && (int) $manager > 0) {
        $m = get_user_by('id', (int) $manager);
        if ($m && !empty($m->display_name)) $manager_name = (string) $m->display_name;
    }

    $main_photo_id = 0;
    if (is_array($main_photo) && !empty($main_photo['ID'])) $main_photo_id = (int) $main_photo['ID'];
    if (is_numeric($main_photo)) $main_photo_id = (int) $main_photo;

    $current_tenancy_id = function_exists('ppc_get_current_tenancy_id_for_property')
        ? ppc_get_current_tenancy_id_for_property($property_id)
        : 0;

    $open_repairs = get_posts([
        'post_type'      => 'ppm_repair',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'repair_property',
                'value'   => (string) $property_id,
                'compare' => '=',
            ],
            [
                'relation' => 'OR',
                [
                    'key'     => 'repair_status',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => 'repair_status',
                    'value'   => ['complete', 'cancelled'],
                    'compare' => 'NOT IN',
                ],
            ],
        ],
    ]);

    $active_voids = get_posts([
        'post_type'      => 'ppm_void',
        'post_status'    => 'publish',
        'posts_per_page' => 5,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'void_property',
                'value'   => (string) $property_id,
                'compare' => '=',
            ],
            [
                'relation' => 'OR',
                [
                    'key'     => 'void_stage',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => 'void_stage',
                    'value'   => ['completed'],
                    'compare' => 'NOT IN',
                ],
            ],
        ],
    ]);

    $tenancies = get_posts([
        'post_type'      => 'ppm_tenancy',
        'post_status'    => 'publish',
        'posts_per_page' => 20,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [
            [
                'key'     => 'tenancy_property',
                'value'   => (string) $property_id,
                'compare' => '=',
            ],
        ],
    ]);

    ob_start(); ?>
    <div class="ppc-stack">
        <header class="ppc-header">
            <div>
                <h1 class="ppc-h1"><?php echo esc_html(get_the_title($property_id) ?: 'Property'); ?></h1>
                <div class="ppc-muted">Property details and activity.</div>
            </div>
            <div class="ppc-actions">
                <?php echo ppc_btn('Edit Property', ppc_edit_url('property', $property_id)); ?>
                <?php echo ppc_btn('+ Start Tenancy', add_query_arg(['property_id' => $property_id], ppc_portal_url('add-tenancy'))); ?>
                <?php echo ppc_btn('+ Add Repair', ppc_portal_url('add-repair')); ?>
            </div>
        </header>

        <section class="ppc-card">
            <h2 class="ppc-h2">Details</h2>
            <div class="ppc-property-details-grid">
                <div class="ppc-property-details-grid__media">
                    <?php if ($main_photo_id > 0): ?>
                        <?php echo wp_get_attachment_image($main_photo_id, 'large', false, ['class' => 'ppc-property-image']); ?>
                    <?php endif; ?>
                </div>
                <div class="ppc-property-details-grid__content">
                    <p><strong>Address:</strong> <?php echo esc_html(trim(implode(', ', array_filter([$line1, $line2, $town, $county, $postcode])) ) ?: '-'); ?></p>
                    <p><strong>Property Code:</strong> <?php echo esc_html($code ?: '-'); ?></p>
                    <p><strong>Region:</strong> <?php echo esc_html($region ?: '-'); ?></p>
                    <p><strong>Status:</strong> <?php echo esc_html($status ?: '-'); ?></p>
                    <p><strong>Property Manager:</strong> <?php echo esc_html($manager_name ?: '-'); ?></p>
                    <?php if (!empty($notes)): ?>
                        <p><strong>Notes:</strong> <?php echo esc_html($notes); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="ppc-card">
            <h2 class="ppc-h2">Current Tenancy</h2>
            <?php if ($current_tenancy_id > 0): ?>
                <?php
                $tenant_id = function_exists('get_field') ? (int) get_field('tenancy_tenant', $current_tenancy_id) : 0;
                $tenant_title = $tenant_id ? (get_the_title($tenant_id) ?: '-') : '-';
                $start = function_exists('get_field') ? get_field('tenancy_start', $current_tenancy_id) : '';
                ?>
                <p><strong>Tenant:</strong> <?php echo esc_html($tenant_title); ?></p>
                <p><strong>Start:</strong> <?php echo esc_html(ppc_fmt_date($start) ?: '-'); ?></p>
                <p><?php echo ppc_btn('View Tenancy', ppc_edit_url('tenancy', $current_tenancy_id)); ?></p>
            <?php else: ?>
                <p>No active tenancy.</p>
            <?php endif; ?>
        </section>

        <section class="ppc-card">
            <h2 class="ppc-h2">Open Repairs</h2>
            <?php if (empty($open_repairs)): ?>
                <p>No open repairs.</p>
            <?php else: ?>
                <div class="ppc-table-wrap">
                    <table class="ppc-table ppc-table--min820">
                        <thead>
                        <tr>
                            <th class="ppc-th">Summary</th>
                            <th class="ppc-th">Owner</th>
                            <th class="ppc-th">Priority</th>
                            <th class="ppc-th">Status</th>
                            <th class="ppc-th">Due</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($open_repairs as $r): ?>
                            <?php
                            $summary = function_exists('get_field') ? (string) get_field('repair_summary', (int) $r->ID) : '';
                            $owner = ppc_get_owner_name_from_field('repair_owner', (int) $r->ID);
                            $priority = function_exists('get_field') ? (string) get_field('repair_priority', (int) $r->ID) : '';
                            $repair_status = function_exists('get_field') ? (string) get_field('repair_status', (int) $r->ID) : '';
                            $due = function_exists('get_field') ? get_field('repair_due_date', (int) $r->ID) : '';
                            ?>
                            <tr>
                                <td class="ppc-td"><a class="ppc-link" href="<?php echo esc_url(ppc_edit_url('repair', (int) $r->ID)); ?>"><?php echo esc_html($summary ?: ($r->post_title ?: '-')); ?></a></td>
                                <td class="ppc-td"><?php echo esc_html($owner ?: '-'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($priority ?: '-'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($repair_status ?: '-'); ?></td>
                                <td class="ppc-td"><?php echo esc_html(ppc_fmt_date($due) ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="ppc-card">
            <h2 class="ppc-h2">Active Voids</h2>
            <?php if (empty($active_voids)): ?>
                <p>No active voids.</p>
            <?php else: ?>
                <div class="ppc-table-wrap">
                    <table class="ppc-table ppc-table--min720">
                        <thead>
                        <tr>
                            <th class="ppc-th">Stage</th>
                            <th class="ppc-th">Start</th>
                            <th class="ppc-th">Target</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($active_voids as $v): ?>
                            <?php
                            $stage = function_exists('get_field') ? (string) get_field('void_stage', (int) $v->ID) : '';
                            $start = function_exists('get_field') ? get_field('void_start_date', (int) $v->ID) : '';
                            $target = function_exists('get_field') ? get_field('void_target_date', (int) $v->ID) : '';
                            ?>
                            <tr>
                                <td class="ppc-td"><a class="ppc-link" href="<?php echo esc_url(ppc_edit_url('void', (int) $v->ID)); ?>"><?php echo esc_html($stage ?: '-'); ?></a></td>
                                <td class="ppc-td"><?php echo esc_html(ppc_fmt_date($start) ?: '-'); ?></td>
                                <td class="ppc-td"><?php echo esc_html(ppc_fmt_date($target) ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="ppc-card">
            <h2 class="ppc-h2">Tenancy History</h2>
            <?php if (empty($tenancies)): ?>
                <p>No tenancies found.</p>
            <?php else: ?>
                <div class="ppc-table-wrap">
                    <table class="ppc-table ppc-table--min820">
                        <thead>
                        <tr>
                            <th class="ppc-th">Tenant</th>
                            <th class="ppc-th">Start</th>
                            <th class="ppc-th">End</th>
                            <th class="ppc-th">Record</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tenancies as $x): ?>
                            <?php
                            $tenant_id = function_exists('get_field') ? (int) get_field('tenancy_tenant', (int) $x->ID) : 0;
                            $tenant_title = $tenant_id ? (get_the_title($tenant_id) ?: '-') : '-';
                            $start = function_exists('get_field') ? get_field('tenancy_start', (int) $x->ID) : '';
                            $end = function_exists('get_field') ? get_field('tenancy_end', (int) $x->ID) : '';
                            ?>
                            <tr>
                                <td class="ppc-td">
                                    <?php if ($tenant_id > 0): ?>
                                        <a class="ppc-link" href="<?php echo esc_url(ppc_edit_url('tenant', $tenant_id)); ?>"><?php echo esc_html($tenant_title); ?></a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="ppc-td"><?php echo esc_html(ppc_fmt_date($start) ?: '-'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($end ? ppc_fmt_date($end) : 'Current'); ?></td>
                                <td class="ppc-td"><a class="ppc-link" href="<?php echo esc_url(ppc_edit_url('tenancy', (int) $x->ID)); ?>">View / Edit</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <?php
    return ob_get_clean();
});

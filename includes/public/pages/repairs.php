<?php
if (!defined('ABSPATH')) exit;

/**
 * REPAIRS overview page
 * Usage: [ppc_repairs_overview]
 */
add_shortcode('ppc_repairs_overview', function () {
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) {
        return '<p>Access denied.</p>';
    }

    $repairs = get_posts([
        'post_type'      => 'ppm_repair',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    ob_start(); ?>

    <div class="ppc-stack">
        <header class="ppc-header">
            <div>
                <h1 class="ppc-h1">Repairs</h1>
                <div class="ppc-muted">View and manage repairs across all properties.</div>
            </div>

            <div class="ppc-actions">
                <?php echo ppc_btn('+ Add Repair', ppc_portal_url('add-repair')); ?>
            </div>
        </header>

        <section class="ppc-card">
            <?php if (empty($repairs)): ?>
                <p>No repairs found.</p>
            <?php else: ?>
                <div class="ppc-table-wrap">
                    <table class="ppc-table ppc-table--min820">
                        <thead>
                        <tr>
                            <th class="ppc-th">Repair</th>
                            <th class="ppc-th">Property</th>
                            <th class="ppc-th">Owner</th>
                            <th class="ppc-th">Priority</th>
                            <th class="ppc-th">Status</th>
                            <th class="ppc-th">Target</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($repairs as $r): ?>
                            <?php
                            $prop_title = ppc_get_property_title_from_field('repair_property', (int)$r->ID);
                            $owner_name = ppc_get_owner_name_from_field('repair_owner', (int)$r->ID);
                            $priority   = function_exists('get_field') ? (string) get_field('repair_priority', $r->ID) : '';
                            $status     = function_exists('get_field') ? (string) get_field('repair_status', $r->ID) : '';
                            $due        = function_exists('get_field') ? get_field('repair_due_date', $r->ID) : '';
                            $summary    = function_exists('get_field') ? (string) get_field('repair_summary', $r->ID) : '';
                            ?>
                            <tr>
                                <td class="ppc-td">
                                    <a class="ppc-link" href="<?php echo esc_url(ppc_page_url('repair', (int)$r->ID)); ?>">
                                        <?php echo esc_html($summary ?: ($r->post_title ?: '—')); ?>
                                    </a>
                                </td>
                                <td class="ppc-td"><?php echo esc_html($prop_title ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($owner_name ?: 'Unassigned'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($priority ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($status ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html(ppc_fmt_date($due) ?: '—'); ?></td>
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
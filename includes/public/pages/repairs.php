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

    // Get all repairs for counting
    $all_repairs = get_posts([
        'post_type'      => 'ppm_repair',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    // Count repairs by status and priority
    $open_count = 0;
    $urgent_count = 0;
    $emergency_count = 0;
    $overdue_count = 0;
    $today = date('Y-m-d');

    foreach ($all_repairs as $repair_id) {
        $status = function_exists('get_field') ? (string) get_field('repair_status', $repair_id) : '';
        $priority = function_exists('get_field') ? (string) get_field('repair_priority', $repair_id) : '';
        $due_date = function_exists('get_field') ? get_field('repair_due_date', $repair_id) : '';
        
        // Skip completed/cancelled repairs
        if (in_array($status, ['complete', 'cancelled'])) continue;
        
        $open_count++;
        
        if ($priority === 'urgent') $urgent_count++;
        if ($priority === 'emergency') $emergency_count++;
        
        // Check if overdue
        if ($due_date && $due_date < $today) $overdue_count++;
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
            <h2 class="ppc-h2">Summary</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px;">
                <div style="text-align: center; padding: 16px; background: var(--ppc-color-bg-lightest); border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: 800; color: var(--ppc-color-h1);"><?php echo $open_count; ?></div>
                    <div style="font-size: 13px; color: var(--ppc-color-text-muted);">Open Repairs</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #fff3cd; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: 800; color: #856404;"><?php echo $urgent_count; ?></div>
                    <div style="font-size: 13px; color: #856404;">Urgent</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #f8d7da; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: 800; color: #721c24;"><?php echo $emergency_count; ?></div>
                    <div style="font-size: 13px; color: #721c24;">Emergency</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #f5c6cb; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: 800; color: #721c24;"><?php echo $overdue_count; ?></div>
                    <div style="font-size: 13px; color: #721c24;">Overdue</div>
                </div>
            </div>
        </section>

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
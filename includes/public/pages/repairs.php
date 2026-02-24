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

    // Get filter parameters
    $filter_property = isset($_GET['property']) ? (int) $_GET['property'] : 0;
    $filter_priority = isset($_GET['priority']) ? sanitize_text_field($_GET['priority']) : '';
    $filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $filter_overdue = isset($_GET['overdue']) ? (bool) $_GET['overdue'] : false;
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

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

    // Build query args for filtered repairs
    $query_args = [
        'post_type'      => 'ppm_repair',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $meta_query = [];

    if ($filter_property > 0) {
        $meta_query[] = [
            'key'     => 'repair_property',
            'value'   => (string) $filter_property,
            'compare' => '=',
        ];
    }

    if ($filter_priority) {
        $meta_query[] = [
            'key'     => 'repair_priority',
            'value'   => $filter_priority,
            'compare' => '=',
        ];
    }

    if ($filter_status) {
        $meta_query[] = [
            'key'     => 'repair_status',
            'value'   => $filter_status,
            'compare' => '=',
        ];
    }

    if ($filter_overdue) {
        $meta_query[] = [
            'key'     => 'repair_due_date',
            'value'   => date('Y-m-d'),
            'compare' => '<',
            'type'    => 'DATE',
        ];
    }

    if ($search) {
        $query_args['s'] = $search;
    }

    if (!empty($meta_query)) {
        $query_args['meta_query'] = $meta_query;
    }

    $repairs = get_posts($query_args);

    // Get all properties for filter dropdown
    $properties = get_posts([
        'post_type'      => 'ppm_property',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
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
                <a href="<?php echo esc_url(add_query_arg(['status' => ''], ppc_portal_url('repairs'))); ?>" class="ppc-summary-card ppc-summary-card--white">
                    <div style="font-size: 24px; font-weight: 800; color: var(--ppc-color-h1);"><?php echo $open_count; ?></div>
                    <div style="font-size: 13px; color: var(--ppc-color-text-muted);">Open Repairs</div>
                </a>
                <a href="<?php echo esc_url(add_query_arg(['priority' => 'urgent'], ppc_portal_url('repairs'))); ?>" class="ppc-summary-card" style="background: #fff3cd;">
                    <div style="font-size: 24px; font-weight: 800; color: #856404;"><?php echo $urgent_count; ?></div>
                    <div style="font-size: 13px; color: #856404;">Urgent</div>
                </a>
                <a href="<?php echo esc_url(add_query_arg(['priority' => 'emergency'], ppc_portal_url('repairs'))); ?>" class="ppc-summary-card" style="background: #f8d7da;">
                    <div style="font-size: 24px; font-weight: 800; color: #721c24;"><?php echo $emergency_count; ?></div>
                    <div style="font-size: 13px; color: #721c24;">Emergency</div>
                </a>
                <a href="<?php echo esc_url(add_query_arg(['overdue' => '1'], ppc_portal_url('repairs'))); ?>" class="ppc-summary-card" style="background: #f5c6cb;">
                    <div style="font-size: 24px; font-weight: 800; color: #721c24;"><?php echo $overdue_count; ?></div>
                    <div style="font-size: 13px; color: #721c24;">Overdue</div>
                </a>
            </div>
        </section>

        <section class="ppc-card">
            <h2 class="ppc-h2">Search & Filter</h2>
            <form method="get" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 20px;">
                <input type="text" name="search" placeholder="Search repairs..." value="<?php echo esc_attr($search); ?>" style="padding: 8px; border: 1px solid var(--ppc-color-border); border-radius: 4px;">

                <select name="property" style="padding: 8px; border: 1px solid var(--ppc-color-border); border-radius: 4px;">
                    <option value="">All Properties</option>
                    <?php foreach ($properties as $prop): ?>
                        <option value="<?php echo esc_attr($prop->ID); ?>" <?php selected($filter_property, $prop->ID); ?>>
                            <?php echo esc_html($prop->post_title ?: 'Untitled'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="priority" style="padding: 8px; border: 1px solid var(--ppc-color-border); border-radius: 4px;">
                    <option value="">All Priorities</option>
                    <option value="low" <?php selected($filter_priority, 'low'); ?>>Low</option>
                    <option value="medium" <?php selected($filter_priority, 'medium'); ?>>Medium</option>
                    <option value="high" <?php selected($filter_priority, 'high'); ?>>High</option>
                    <option value="urgent" <?php selected($filter_priority, 'urgent'); ?>>Urgent</option>
                    <option value="emergency" <?php selected($filter_priority, 'emergency'); ?>>Emergency</option>
                </select>

                <select name="status" style="padding: 8px; border: 1px solid var(--ppc-color-border); border-radius: 4px;">
                    <option value="">All Statuses</option>
                    <option value="open" <?php selected($filter_status, 'open'); ?>>Open</option>
                    <option value="in_progress" <?php selected($filter_status, 'in_progress'); ?>>In Progress</option>
                    <option value="pending" <?php selected($filter_status, 'pending'); ?>>Pending</option>
                    <option value="complete" <?php selected($filter_status, 'complete'); ?>>Complete</option>
                    <option value="cancelled" <?php selected($filter_status, 'cancelled'); ?>>Cancelled</option>
                </select>

                <div style="display: flex; gap: 8px; align-items: center;">
                    <button type="submit" class="ppc-btn ppc-btn--compact">Apply</button>
                    <a href="<?php echo esc_url(ppc_portal_url('repairs')); ?>" class="ppc-btn ppc-btn--compact">Clear</a>
                </div>
            </form>
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

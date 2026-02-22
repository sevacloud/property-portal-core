<?php
if (!defined('ABSPATH')) exit;

/**
 * Helpers: build front-end portal URLs.
 */
function ppc_portal_url(string $path): string {
    return home_url('/property-management/' . ltrim($path, '/') . '/');
}
function ppc_edit_url(string $type, int $id): string {
    switch ($type) {
        case 'property': return add_query_arg(['id' => $id], ppc_portal_url('edit-property'));
        case 'repair':   return add_query_arg(['id' => $id], ppc_portal_url('edit-repair'));
        case 'void':     return add_query_arg(['id' => $id], ppc_portal_url('edit-void'));
        default:         return ppc_portal_url('');
    }
}

/**
 * Layout shell with sidebar.
 * Usage: [ppc_portal_layout content="dashboard"]
 */
add_shortcode('ppc_portal_layout', function ($atts) {
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) {
        return '<p>Access denied.</p>';
    }

    $atts = shortcode_atts(['content' => 'dashboard'], $atts);
    $content = (string) $atts['content'];

    $links = [
        'Dashboard'    => ppc_portal_url(''),
        'Add Repair'   => ppc_portal_url('add-repair'),
        'Add Property' => ppc_portal_url('add-property'),
        'Add Void'     => ppc_portal_url('add-void'),
    ];

    ob_start(); ?>
    <div class="ppc-app" style="display:grid;grid-template-columns:260px 1fr;gap:18px;max-width:1200px;margin:0 auto;">
        <aside style="border:1px solid #ddd;border-radius:14px;padding:14px;background:#fff;height:fit-content;position:sticky;top:20px;">
            <div style="font-weight:800;font-size:16px;margin-bottom:10px;">Property Portal</div>
            <nav style="display:flex;flex-direction:column;gap:8px;">
                <?php foreach ($links as $label => $url): ?>
                    <a href="<?php echo esc_url($url); ?>"
                       style="text-decoration:none;border:1px solid #cfcfcf;border-radius:10px;padding:10px 12px;color:#111;background:#fff;">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>

                <a href="<?php echo esc_url(wp_logout_url(home_url('/staff-login/'))); ?>"
                   style="text-decoration:none;margin-top:10px;color:#b42318;font-weight:700;">
                    Log out
                </a>
            </nav>
        </aside>

        <main style="min-width:0;">
            <?php
            switch ($content) {
                case 'add_property':  echo do_shortcode('[ppc_property_form mode="create"]'); break;
                case 'edit_property': echo do_shortcode('[ppc_property_form mode="edit"]');   break;
                case 'add_repair':    echo do_shortcode('[ppc_repair_form mode="create"]');   break;
                case 'edit_repair':   echo do_shortcode('[ppc_repair_form mode="edit"]');     break;
                case 'add_void':      echo do_shortcode('[ppc_void_form mode="create"]');     break;
                case 'edit_void':     echo do_shortcode('[ppc_void_form mode="edit"]');       break;
                default:              echo do_shortcode('[ppc_pm_dashboard]');                break;
            }
            ?>
        </main>
    </div>
    <?php
    return ob_get_clean();
});

/**
 * Dashboard (default portal landing view).
 * Used by ppc_portal_layout when content="dashboard" (default).
 */
add_shortcode('ppc_pm_dashboard', function () {
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) {
        return '<p>Access denied.</p>';
    }

    $user_id = get_current_user_id();

    // "Open" statuses (everything except complete/cancelled)
    $closed_statuses = ['complete', 'cancelled'];

    // Helper: safely get a property title from the ACF post_object field (stored as ID in meta)
    $get_property_title = function (int $repair_id): string {
        $prop = get_field('repair_property', $repair_id);
        if (is_object($prop) && !empty($prop->post_title)) return (string) $prop->post_title;
        if (is_numeric($prop) && (int)$prop > 0) return get_the_title((int)$prop) ?: '';
        return '';
    };

    // Helper: owner display
    $get_owner_name = function (int $repair_id): string {
        $owner = get_field('repair_owner', $repair_id);
        if (is_array($owner) && !empty($owner['display_name'])) return (string) $owner['display_name'];
        if (is_object($owner) && !empty($owner->display_name)) return (string) $owner->display_name;
        if (is_numeric($owner) && (int)$owner > 0) {
            $u = get_user_by('id', (int)$owner);
            return $u ? (string) $u->display_name : '';
        }
        return '';
    };

    // Helper: simple date formatting (stored as Y-m-d)
    $fmt_date = function ($ymd): string {
        if (!$ymd) return '';
        $ts = strtotime((string)$ymd);
        if (!$ts) return '';
        return date('d M Y', $ts);
    };

    // Query: My Open Repairs
    $my_repairs = get_posts([
        'post_type'      => 'ppm_repair',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'repair_owner',
                'value'   => (string) $user_id,
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
                    'value'   => $closed_statuses,
                    'compare' => 'NOT IN',
                ],
            ],
        ],
    ]);

    // Query: All Open Repairs
    $open_repairs = get_posts([
        'post_type'      => 'ppm_repair',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => 'repair_status',
                'compare' => 'NOT EXISTS',
            ],
            [
                'key'     => 'repair_status',
                'value'   => $closed_statuses,
                'compare' => 'NOT IN',
            ],
        ],
    ]);

    // Query: Active Voids (not completed)
    $active_voids = get_posts([
        'post_type'      => 'ppm_void',
        'post_status'    => 'publish',
        'posts_per_page' => 8,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [
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
    ]);

    ob_start(); ?>

    <div style="display:flex;flex-direction:column;gap:16px;">
        <header style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <h1 style="margin:0 0 6px 0;">Dashboard</h1>
                <div style="color:#555;">Quick view of repairs and voids.</div>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="<?php echo esc_url(ppc_portal_url('add-repair')); ?>"
                   style="text-decoration:none;border:1px solid #cfcfcf;border-radius:10px;padding:10px 12px;color:#111;background:#fff;font-weight:700;">
                    + Add Repair
                </a>
                <a href="<?php echo esc_url(ppc_portal_url('add-property')); ?>"
                   style="text-decoration:none;border:1px solid #cfcfcf;border-radius:10px;padding:10px 12px;color:#111;background:#fff;font-weight:700;">
                    + Add Property
                </a>
                <a href="<?php echo esc_url(ppc_portal_url('add-void')); ?>"
                   style="text-decoration:none;border:1px solid #cfcfcf;border-radius:10px;padding:10px 12px;color:#111;background:#fff;font-weight:700;">
                    + Start Void
                </a>
            </div>
        </header>

        <section style="border:1px solid #ddd;border-radius:14px;padding:14px;background:#fff;">
            <h2 style="margin:0 0 10px 0;font-size:16px;">My Open Repairs</h2>

            <?php if (empty($my_repairs)): ?>
                <p style="margin:0;color:#555;">No open repairs assigned to you.</p>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table style="width:100%;border-collapse:collapse;min-width:720px;">
                        <thead>
                        <tr>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Property</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Summary</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Priority</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Status</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Target</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($my_repairs as $r): ?>
                            <?php
                            $prop_title = $get_property_title($r->ID);
                            $priority   = (string) get_field('repair_priority', $r->ID);
                            $status     = (string) get_field('repair_status', $r->ID);
                            $due        = get_field('repair_due_date', $r->ID);
                            ?>
                            <tr>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($prop_title ?: '—'); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html(get_field('repair_summary', $r->ID) ?: $r->post_title); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($priority ?: '—'); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($status ?: '—'); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($fmt_date($due) ?: '—'); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;">
                                    <a href="<?php echo esc_url(ppc_edit_url('repair', (int)$r->ID)); ?>" style="font-weight:700;">View / Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section style="border:1px solid #ddd;border-radius:14px;padding:14px;background:#fff;">
            <h2 style="margin:0 0 10px 0;font-size:16px;">All Open Repairs</h2>

            <?php if (empty($open_repairs)): ?>
                <p style="margin:0;color:#555;">No open repairs found.</p>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table style="width:100%;border-collapse:collapse;min-width:820px;">
                        <thead>
                        <tr>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Property</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Summary</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Owner</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Priority</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Status</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Target</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($open_repairs as $r): ?>
                            <?php
                            $prop_title = $get_property_title($r->ID);
                            $owner_name = $get_owner_name($r->ID);
                            $priority   = (string) get_field('repair_priority', $r->ID);
                            $status     = (string) get_field('repair_status', $r->ID);
                            $due        = get_field('repair_due_date', $r->ID);
                            ?>
                            <tr>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($prop_title ?: '—'); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html(get_field('repair_summary', $r->ID) ?: $r->post_title); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($owner_name ?: 'Unassigned'); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($priority ?: '—'); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($status ?: '—'); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($fmt_date($due) ?: '—'); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;">
                                    <a href="<?php echo esc_url(ppc_edit_url('repair', (int)$r->ID)); ?>" style="font-weight:700;">View / Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section style="border:1px solid #ddd;border-radius:14px;padding:14px;background:#fff;">
            <h2 style="margin:0 0 10px 0;font-size:16px;">Active Voids</h2>

            <?php if (empty($active_voids)): ?>
                <p style="margin:0;color:#555;">No active voids found.</p>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table style="width:100%;border-collapse:collapse;min-width:720px;">
                        <thead>
                        <tr>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Property</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Stage</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Start</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Target</th>
                            <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($active_voids as $v): ?>
                            <?php
                            $prop = get_field('void_property', $v->ID);
                            $prop_title = is_object($prop) && !empty($prop->post_title)
                                ? (string) $prop->post_title
                                : (is_numeric($prop) ? (get_the_title((int)$prop) ?: '') : '');

                            $stage = (string) get_field('void_stage', $v->ID);
                            $start = get_field('void_start_date', $v->ID);
                            $target = get_field('void_target_date', $v->ID);
                            ?>
                            <tr>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($prop_title ?: '—'); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($stage ?: '—'); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($fmt_date($start) ?: '—'); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;"><?php echo esc_html($fmt_date($target) ?: '—'); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f2f2f2;">
                                    <a href="<?php echo esc_url(ppc_edit_url('void', (int)$v->ID)); ?>" style="font-weight:700;">View / Edit</a>
                                </td>
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

/**
 * PROPERTY create/edit form.
 */
add_shortcode('ppc_property_form', function ($atts) {
    if (!function_exists('acf_form')) return '<p>ACF is required.</p>';
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) return '<p>Access denied.</p>';

    $atts = shortcode_atts(['mode' => 'create'], $atts);
    $mode = (string) $atts['mode'];

    $post_id = 'new_post';
    if ($mode === 'edit') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id || get_post_type($id) !== 'ppm_property') return '<p>Invalid property.</p>';
        $post_id = $id;
    }

    ob_start();
    echo '<h1 style="margin-top:0;">' . esc_html($mode === 'edit' ? 'Edit Property' : 'Add Property') . '</h1>';

    acf_form([
        'post_id' => $post_id,
        'new_post' => [
            'post_type'   => 'ppm_property',
            'post_status' => 'publish',
        ],
        'field_groups' => ['group_ppc_property_fields'],
        'submit_value' => $mode === 'edit' ? 'Save Property' : 'Create Property',
        'return'       => ppc_portal_url(''),
    ]);

    return ob_get_clean();
});

/**
 * REPAIR create/edit form.
 */
add_shortcode('ppc_repair_form', function ($atts) {
    if (!function_exists('acf_form')) return '<p>ACF is required.</p>';
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) return '<p>Access denied.</p>';

    $atts = shortcode_atts(['mode' => 'create'], $atts);
    $mode = (string) $atts['mode'];

    $post_id = 'new_post';
    if ($mode === 'edit') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id || get_post_type($id) !== 'ppm_repair') return '<p>Invalid repair.</p>';
        $post_id = $id;
    }

    ob_start();
    echo '<h1 style="margin-top:0;">' . esc_html($mode === 'edit' ? 'Edit Repair' : 'Add Repair') . '</h1>';

    acf_form([
        'post_id' => $post_id,
        'new_post' => [
            'post_type'   => 'ppm_repair',
            'post_status' => 'publish',
        ],
        'field_groups' => ['group_ppc_repair_fields'],
        'submit_value' => $mode === 'edit' ? 'Save Repair' : 'Create Repair',
        'return'       => ppc_portal_url(''),
    ]);

    return ob_get_clean();
});

/**
 * VOID create/edit form.
 */
add_shortcode('ppc_void_form', function ($atts) {
    if (!function_exists('acf_form')) return '<p>ACF is required.</p>';
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) return '<p>Access denied.</p>';

    // If void fields aren't registered yet, avoid fatal/blank form.
    if (!function_exists('acf_get_field_group') || !acf_get_field_group('group_ppc_void_fields')) {
        return '<p>Void fields are not registered yet. Next step: add group_ppc_void_fields in your plugin.</p>';
    }

    $atts = shortcode_atts(['mode' => 'create'], $atts);
    $mode = (string) $atts['mode'];

    $post_id = 'new_post';
    if ($mode === 'edit') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id || get_post_type($id) !== 'ppm_void') return '<p>Invalid void.</p>';
        $post_id = $id;
    }

    ob_start();
    echo '<h1 style="margin-top:0;">' . esc_html($mode === 'edit' ? 'Edit Void' : 'Start Void') . '</h1>';

    acf_form([
        'post_id' => $post_id,
        'new_post' => [
            'post_type'   => 'ppm_void',
            'post_status' => 'publish',
        ],
        'field_groups' => ['group_ppc_void_fields'],
        'submit_value' => $mode === 'edit' ? 'Save Void' : 'Create Void',
        'return'       => ppc_portal_url(''),
    ]);

    return ob_get_clean();
});

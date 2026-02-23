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
        case 'tenant':   return add_query_arg(['id' => $id], ppc_portal_url('edit-tenant'));
        case 'tenancy':  return add_query_arg(['id' => $id], ppc_portal_url('edit-tenancy'));
        default:         return ppc_portal_url('');
    }
}

/**
 * Enqueue the CSS only on portal pages
 * (So it doesn’t affect the rest of the site)
 */
add_action('wp_enqueue_scripts', function () {
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) return;

    $slugs = [
        'property-management',
        'properties', 'repairs', 'voids', 'tenants', 'tenancies',
        'add-property', 'edit-property',
        'add-repair', 'edit-repair',
        'add-void', 'edit-void',
        'add-tenant', 'edit-tenant',
        'add-tenancy', 'edit-tenancy',
    ];
    if (!is_page($slugs)) return;

    $rel_path = 'assets/css/ppc-portal.css';
    $file_path = PPC_PATH . $rel_path;
    $ver = file_exists($file_path) ? (string) filemtime($file_path) : '0.1.0';

    wp_enqueue_style('ppc-portal', PPC_URL . $rel_path, [], $ver);
});

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
        'Dashboard'  => ppc_portal_url(''),
        'Properties' => ppc_portal_url('properties'),
        'Repairs'    => ppc_portal_url('repairs'),
        'Voids'      => ppc_portal_url('voids'),
        'Tenants'    => ppc_portal_url('tenants'),
        'Tenancies'  => ppc_portal_url('tenancies'),
    ];

    ob_start(); ?>
    <div class="ppc-app">
        <aside class="ppc-sidebar">
            <h2 class="ppc-sidebar__title ppc-h2">Property Portal</h2>
            <nav class="ppc-nav">
                <?php foreach ($links as $label => $url): ?>
                    <a class="ppc-btn" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>

                <a class="ppc-btn ppc-btn--danger"
                    href="<?php echo esc_url(wp_logout_url(home_url('/staff-login/'))); ?>">
                    Log out
                </a>
            </nav>
        </aside>

        <main class="ppc-main">
            <?php
            switch ($content) {
                case 'properties':    echo do_shortcode('[ppc_properties_overview]');         break;
                case 'repairs':       echo do_shortcode('[ppc_repairs_overview]');            break;
                case 'voids':         echo do_shortcode('[ppc_voids_overview]');              break;
                case 'tenants':       echo do_shortcode('[ppc_tenants_overview]');            break;
                case 'tenancies':     echo do_shortcode('[ppc_tenancies_overview]');          break;

                case 'add_property':  echo do_shortcode('[ppc_property_form mode="create"]'); break;
                case 'edit_property': echo do_shortcode('[ppc_property_form mode="edit"]');   break;
                case 'add_repair':    echo do_shortcode('[ppc_repair_form mode="create"]');   break;
                case 'edit_repair':   echo do_shortcode('[ppc_repair_form mode="edit"]');     break;
                case 'add_void':      echo do_shortcode('[ppc_void_form mode="create"]');     break;
                case 'edit_void':     echo do_shortcode('[ppc_void_form mode="edit"]');       break;

                case 'add_tenant':    echo do_shortcode('[ppc_tenant_form mode="create"]');   break;
                case 'edit_tenant':   echo do_shortcode('[ppc_tenant_form mode="edit"]');     break;
                case 'add_tenancy':   echo do_shortcode('[ppc_tenancy_form mode="create"]');  break;
                case 'edit_tenancy':  echo do_shortcode('[ppc_tenancy_form mode="edit"]');    break;

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

    <div class="ppc-stack">
        <header class="ppc-header">
            <div>
                <h1 class="ppc-h1">Dashboard</h1>
                <div class="ppc-muted">Quick view of repairs and voids.</div>
            </div>

            <div class="ppc-actions">
                <?php echo ppc_btn('+ Add Repair', ppc_portal_url('add-repair')); ?>
                <?php echo ppc_btn('+ Add Property', ppc_portal_url('add-property')); ?>
                <?php echo ppc_btn('+ Start Void', ppc_portal_url('add-void')); ?>
            </div>
        </header>

        <section class="ppc-card">
            <h2 class="ppc-h2">My Open Repairs</h2>

            <?php if (empty($my_repairs)): ?>
                <p>No open repairs assigned to you.</p>
            <?php else: ?>
                <div class="ppc-table-wrap">
                    <table class="ppc-table--min720">
                        <thead>
                        <tr>
                            <th class="ppc-th">Property</th>
                            <th class="ppc-th">Summary</th>
                            <th class="ppc-th">Priority</th>
                            <th class="ppc-th">Status</th>
                            <th class="ppc-th">Target</th>
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
                                <td class="ppc-td">
                                    <a class="ppc-link"
                                    href="<?php echo esc_url(ppc_edit_url('repair', (int)$r->ID)); ?>">
                                        <?php echo esc_html(get_field('repair_summary', $r->ID) ?: $r->post_title); ?>
                                    </a>
                                </td>
                                <td class="ppc-td"><?php echo esc_html($prop_title ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($priority ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($status ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($fmt_date($due) ?: '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="ppc-card">
            <h2 class="ppc-h2">All Open Repairs</h2>

            <?php if (empty($open_repairs)): ?>
                <p>No open repairs found.</p>
            <?php else: ?>
                <div class="ppc-table-wrap">
                    <table class="ppc-table--min820">
                        <thead>
                        <tr>
                            <th class="ppc-th">Property</th>
                            <th class="ppc-th">Summary</th>
                            <th class="ppc-th">Owner</th>
                            <th class="ppc-th">Priority</th>
                            <th class="ppc-th">Status</th>
                            <th class="ppc-th">Target</th>
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
                                <td class="ppc-td">
                                    <a class="ppc-link"
                                    href="<?php echo esc_url(ppc_edit_url('repair', (int)$r->ID)); ?>">
                                        <?php echo esc_html(get_field('repair_summary', $r->ID) ?: $r->post_title); ?>
                                    </a>
                                </td>
                                <td class="ppc-td"><?php echo esc_html($prop_title ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($owner_name ?: 'Unassigned'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($priority ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($status ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($fmt_date($due) ?: '—'); ?></td>
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
                <p>No active voids found.</p>
            <?php else: ?>
                <div class="ppc-table-wrap">
                    <table class="ppc-table--min720">
                        <thead>
                        <tr>
                            <th class="ppc-th">Property</th>
                            <th class="ppc-th">Stage</th>
                            <th class="ppc-th">Start</th>
                            <th class="ppc-th">Target</th>
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
                                <td class="ppc-td">
                                    <a class="ppc-link"
                                    href="<?php echo esc_url(ppc_edit_url('void', (int)$v->ID)); ?>">
                                        <?php echo esc_html($prop_title ?: '—'); ?>
                                    </a>
                                </td>
                                <td class="ppc-td"><?php echo esc_html($stage ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($fmt_date($start) ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($fmt_date($target) ?: '—'); ?></td>
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
 * PROPERTIES overview page
 * Usage: [ppc_properties_overview]
 */
add_shortcode('ppc_properties_overview', function () {
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) {
        return '<p>Access denied.</p>';
    }

    $properties = get_posts([
        'post_type'      => 'ppm_property',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    ob_start(); ?>

    <div class="ppc-stack">
        <header class="ppc-header">
            <div>
                <h1 class="ppc-h1">Properties</h1>
                <div class="ppc-muted">View and manage all properties.</div>
            </div>

            <div class="ppc-actions">
                <?php echo ppc_btn('+ Add Property', ppc_portal_url('add-property')); ?>
            </div>
        </header>

        <section class="ppc-card">
            <?php if (empty($properties)): ?>
                <p>No properties found.</p>
            <?php else: ?>
                <div class="ppc-table-wrap">
                    <table class="ppc-table ppc-table--min720">
                        <thead>
                        <tr>
                            <th class="ppc-th">Property</th>
                            <th class="ppc-th">Created</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($properties as $p): ?>
                            <tr>
                                <td class="ppc-td"><?php echo esc_html($p->post_title ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html(date('d M Y', strtotime($p->post_date))); ?></td>
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
                            <th class="ppc-th">Property</th>
                            <th class="ppc-th">Summary</th>
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
                                <td class="ppc-td"><?php echo esc_html($prop_title ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($summary ?: ($r->post_title ?: '—')); ?></td>
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

/**
 * VOIDS overview page
 * Usage: [ppc_voids_overview]
 */
add_shortcode('ppc_voids_overview', function () {
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) {
        return '<p>Access denied.</p>';
    }

    $voids = get_posts([
        'post_type'      => 'ppm_void',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    ob_start(); ?>

    <div class="ppc-stack">
        <header class="ppc-header">
            <div>
                <h1 class="ppc-h1">Voids</h1>
                <div class="ppc-muted">Track active and completed void periods.</div>
            </div>

            <div class="ppc-actions">
                <?php echo ppc_btn('+ Start Void', ppc_portal_url('add-void')); ?>
            </div>
        </header>

        <section class="ppc-card">
            <?php if (empty($voids)): ?>
                <p>No voids found.</p>
            <?php else: ?>
                <div class="ppc-table-wrap">
                    <table class="ppc-table ppc-table--min720">
                        <thead>
                        <tr>
                            <th class="ppc-th">Property</th>
                            <th class="ppc-th">Stage</th>
                            <th class="ppc-th">Start</th>
                            <th class="ppc-th">Target</th>
                            <th class="ppc-th">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($voids as $v): ?>
                            <?php
                            $prop_title = ppc_get_property_title_from_field('void_property', (int)$v->ID);
                            $stage      = function_exists('get_field') ? (string) get_field('void_stage', $v->ID) : '';
                            $start      = function_exists('get_field') ? get_field('void_start_date', $v->ID) : '';
                            $target     = function_exists('get_field') ? get_field('void_target_date', $v->ID) : '';
                            ?>
                            <tr>
                                <td class="ppc-td"><?php echo esc_html($prop_title ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($stage ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html(ppc_fmt_date($start) ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html(ppc_fmt_date($target) ?: '—'); ?></td>
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
 * TENANTS overview page
 * Usage: [ppc_tenants_overview]
 */
add_shortcode('ppc_tenants_overview', function () {
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) {
        return '<p>Access denied.</p>';
    }

    $tenants = get_posts([
        'post_type'      => 'ppm_tenant',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    ob_start(); ?>
    <div class="ppc-stack">
        <header class="ppc-header">
            <div>
                <h1 class="ppc-h1">Tenants</h1>
                <div class="ppc-muted">View and manage tenants.</div>
            </div>
            <div class="ppc-actions">
                <?php echo ppc_btn('+ Add Tenant', ppc_portal_url('add-tenant')); ?>
            </div>
        </header>

        <section class="ppc-card">
            <?php if (empty($tenants)): ?>
                <p>No tenants found.</p>
            <?php else: ?>
                <div class="ppc-table-wrap">
                    <table class="ppc-table ppc-table--min720">
                        <thead>
                        <tr>
                            <th class="ppc-th">Tenant</th>
                            <th class="ppc-th">Phone</th>
                            <th class="ppc-th">Email</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tenants as $t): ?>
                            <?php
                            $phone = function_exists('get_field') ? (string) get_field('tenant_phone', (int)$t->ID) : '';
                            $email = function_exists('get_field') ? (string) get_field('tenant_email', (int)$t->ID) : '';
                            ?>
                            <tr>
                                <td class="ppc-td">
                                    <a class="ppc-link" href="<?php echo esc_url(ppc_edit_url('tenant', (int)$t->ID)); ?>">
                                        <?php echo esc_html($t->post_title ?: '—'); ?>
                                    </a>
                                </td>
                                <td class="ppc-td"><?php echo esc_html($phone ?: '—'); ?></td>
                                <td class="ppc-td"><?php echo esc_html($email ?: '—'); ?></td>
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
 * TENANCY overview page
 * Usage: [ppc_tenancies_overview]
 */
add_shortcode('ppc_tenancies_overview', function () {
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) {
        return '<p>Access denied.</p>';
    }

    $tenancies = get_posts([
        'post_type'      => 'ppm_tenancy',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    $fmt_date = function ($ymd): string {
        if (!$ymd) return '';
        $ts = strtotime((string)$ymd);
        if (!$ts) return '';
        return date('d M Y', $ts);
    };

    ob_start(); ?>
    <div class="ppc-stack">
        <header class="ppc-header">
            <div>
                <h1 class="ppc-h1">Tenancies</h1>
                <div class="ppc-muted">Track tenant-to-property assignments over time.</div>
            </div>
            <div class="ppc-actions">
                <?php echo ppc_btn('+ Start Tenancy', ppc_portal_url('add-tenancy')); ?>
            </div>
        </header>

        <section class="ppc-card">
            <?php if (empty($tenancies)): ?>
                <p>No tenancies found.</p>
            <?php else: ?>
                <div class="ppc-table-wrap">
                    <table class="ppc-table ppc-table--min820">
                        <thead>
                        <tr>
                            <th class="ppc-th">Property</th>
                            <th class="ppc-th">Tenant</th>
                            <th class="ppc-th">Start</th>
                            <th class="ppc-th">End</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tenancies as $x): ?>
                            <?php
                            $property_id = function_exists('get_field') ? (int) get_field('tenancy_property', (int)$x->ID) : 0;
                            $tenant_id   = function_exists('get_field') ? (int) get_field('tenancy_tenant', (int)$x->ID) : 0;
                            $start       = function_exists('get_field') ? get_field('tenancy_start', (int)$x->ID) : '';
                            $end         = function_exists('get_field') ? get_field('tenancy_end', (int)$x->ID) : '';

                            $property_title = $property_id ? (get_the_title($property_id) ?: '') : '';
                            $tenant_title   = $tenant_id ? (get_the_title($tenant_id) ?: '') : '';
                            ?>
                            <tr>
                                <td class="ppc-td">
                                    <?php if ($property_id): ?>
                                        <a class="ppc-link" href="<?php echo esc_url(ppc_edit_url('property', $property_id)); ?>">
                                            <?php echo esc_html($property_title ?: '—'); ?>
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td class="ppc-td">
                                    <?php if ($tenant_id): ?>
                                        <a class="ppc-link" href="<?php echo esc_url(ppc_edit_url('tenant', $tenant_id)); ?>">
                                            <?php echo esc_html($tenant_title ?: '—'); ?>
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td class="ppc-td"><?php echo esc_html($fmt_date($start) ?: '—'); ?></td>
                                <td class="ppc-td">
                                    <a class="ppc-link" href="<?php echo esc_url(ppc_edit_url('tenancy', (int)$x->ID)); ?>">
                                        <?php echo esc_html($end ? $fmt_date($end) : 'Current'); ?>
                                    </a>
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
    echo '<h1 class="ppc-h1">' . esc_html($mode === 'edit' ? 'Edit Property' : 'Add Property') . '</h1>';

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
    echo '<h1 class="ppc-h1">' . esc_html($mode === 'edit' ? 'Edit Repair' : 'Add Repair') . '</h1>';

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
    echo '<h1 class="ppc-h1">' . esc_html($mode === 'edit' ? 'Edit Void' : 'Start Void') . '</h1>';

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

/**
 * TENANT create/edit form.
 */
add_shortcode('ppc_tenant_form', function ($atts) {
    if (!function_exists('acf_form')) return '<p>ACF is required.</p>';
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) return '<p>Access denied.</p>';

    $atts = shortcode_atts(['mode' => 'create'], $atts);
    $mode = (string) $atts['mode'];

    $post_id = 'new_post';
    $tenant_id = 0;

    if ($mode === 'edit') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id || get_post_type($id) !== 'ppm_tenant') return '<p>Invalid tenant.</p>';
        $post_id = $id;
        $tenant_id = $id;
    }

    // Helper: date formatting
    $fmt_date = function ($ymd): string {
        if (!$ymd) return '';
        $ts = strtotime((string)$ymd);
        if (!$ts) return '';
        return date('d M Y', $ts);
    };

    ob_start();

    echo '<div class="ppc-stack">';
    echo '<header class="ppc-header">';
    echo '<div><h1 class="ppc-h1">' . esc_html($mode === 'edit' ? 'Edit Tenant' : 'Add Tenant') . '</h1></div>';

    // Show "Start Tenancy" action only when we have a real tenant ID
    if ($mode === 'edit' && $tenant_id > 0) {
        $add_tenancy_url = add_query_arg(['tenant_id' => $tenant_id], ppc_portal_url('add-tenancy'));
        echo '<div class="ppc-actions">' . ppc_btn('+ Start Tenancy', $add_tenancy_url) . '</div>';
    }
    echo '</header>';

    // Tenant form
    acf_form([
        'post_id' => $post_id,
        'new_post' => [
            'post_type'   => 'ppm_tenant',
            'post_status' => 'publish',
        ],
        'field_groups' => ['group_ppc_tenant_fields'],
        'submit_value' => $mode === 'edit' ? 'Save Tenant' : 'Create Tenant',
        'return'       => ppc_portal_url('tenants'),
    ]);

    // Tenancy history (edit mode only)
    if ($mode === 'edit' && $tenant_id > 0) {

        $tenancies = get_posts([
            'post_type'      => 'ppm_tenancy',
            'post_status'    => 'publish',
            'posts_per_page' => 50,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [
                [
                    'key'     => 'tenancy_tenant',
                    'value'   => (string) $tenant_id,
                    'compare' => '=',
                ],
            ],
        ]);

        echo '<section class="ppc-card">';
        echo '<div class="ppc-header">';
        echo '<h2 class="ppc-h2">Tenancy History</h2>';
        echo '</div>';

        if (empty($tenancies)) {
            echo '<p>No tenancies found for this tenant.</p>';
        } else {
            echo '<div class="ppc-table-wrap">';
            echo '<table class="ppc-table ppc-table--min820">';
            echo '<thead><tr>';
            echo '<th class="ppc-th">Property</th>';
            echo '<th class="ppc-th">Start</th>';
            echo '<th class="ppc-th">End</th>';
            echo '<th class="ppc-th">Record</th>';
            echo '</tr></thead><tbody>';

            foreach ($tenancies as $x) {
                $property_id = function_exists('get_field') ? (int) get_field('tenancy_property', (int)$x->ID) : 0;
                $start       = function_exists('get_field') ? get_field('tenancy_start', (int)$x->ID) : '';
                $end         = function_exists('get_field') ? get_field('tenancy_end', (int)$x->ID) : '';
                $is_current  = empty($end);

                $property_title = $property_id ? (get_the_title($property_id) ?: '') : '';

                echo '<tr>';
                echo '<td class="ppc-td">' . ($property_id
                        ? '<a class="ppc-link" href="' . esc_url(ppc_edit_url('property', $property_id)) . '">' . esc_html($property_title ?: '—') . '</a>'
                        : '—'
                    ) . '</td>';
                echo '<td class="ppc-td">' . esc_html($fmt_date($start) ?: '—') . '</td>';
                echo '<td class="ppc-td">' . esc_html($end ? $fmt_date($end) : 'Current') . '</td>';
                echo '<td class="ppc-td">';
                echo '<a class="ppc-link" href="' . esc_url(ppc_edit_url('tenancy', (int)$x->ID)) . '">View / Edit</a>';

                if ($is_current) {
                    $end_url = ppc_end_tenancy_url((int)$x->ID, wp_get_referer() ?: ppc_portal_url('tenants'));
                    echo ' &nbsp; <a class="ppc-btn ppc-btn--danger" href="' . esc_url($end_url) . '">End</a>';
                }

                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table></div>';
        }

        echo '</section>';
    }

    echo '</div>'; // .ppc-stack

    return ob_get_clean();
});

/**
 * TENANCY create/edit form.
 */
add_shortcode('ppc_tenancy_form', function ($atts) {
    if (!function_exists('acf_form')) return '<p>ACF is required.</p>';
    if (!is_user_logged_in() || !function_exists('ppc_is_staff_user') || !ppc_is_staff_user()) return '<p>Access denied.</p>';

    if (!function_exists('acf_get_field_group') || !acf_get_field_group('group_ppc_tenancy_fields')) {
        return '<p>Tenancy fields are not registered yet. Next step: add group_ppc_tenancy_fields in your plugin.</p>';
    }

    $atts = shortcode_atts(['mode' => 'create'], $atts);
    $mode = (string) $atts['mode'];

    $post_id = 'new_post';
    if ($mode === 'edit') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id || get_post_type($id) !== 'ppm_tenancy') return '<p>Invalid tenancy.</p>';
        $post_id = $id;
    }

    // Prefill tenant/property when creating a new tenancy
    $prefill_tenant_id   = ($mode === 'create' && isset($_GET['tenant_id']))   ? (int) $_GET['tenant_id'] : 0;
    $prefill_property_id = ($mode === 'create' && isset($_GET['property_id'])) ? (int) $_GET['property_id'] : 0;

    // ACF: prefill field values for new_post
    $filter_tenant = null;
    $filter_property = null;

    if ($prefill_tenant_id > 0) {
        $filter_tenant = function ($field) use ($prefill_tenant_id) {
            if (empty($field['value'])) $field['value'] = $prefill_tenant_id;
            return $field;
        };
        add_filter('acf/prepare_field/name=tenancy_tenant', $filter_tenant, 20);
    }

    if ($prefill_property_id > 0) {
        $filter_property = function ($field) use ($prefill_property_id) {
            if (empty($field['value'])) $field['value'] = $prefill_property_id;
            return $field;
        };
        add_filter('acf/prepare_field/name=tenancy_property', $filter_property, 20);
    }

    // Prevent starting a new tenancy if the property already has a current tenancy
    if ($mode === 'create' && $prefill_property_id > 0) {
        $current_tenancy_id = ppc_get_current_tenancy_id_for_property($prefill_property_id);

        if ($current_tenancy_id > 0) {

            // Remove filters BEFORE returning early
            if ($filter_tenant) remove_filter('acf/prepare_field/name=tenancy_tenant', $filter_tenant, 20);
            if ($filter_property) remove_filter('acf/prepare_field/name=tenancy_property', $filter_property, 20);

            $property_title = get_the_title($prefill_property_id) ?: 'this property';

            $view_url = ppc_edit_url('tenancy', $current_tenancy_id);
            $end_url  = ppc_end_tenancy_url(
                $current_tenancy_id,
                ppc_portal_url('add-tenancy') . '?property_id=' . $prefill_property_id
            );

            ob_start(); ?>
            <div class="ppc-stack">
                <header class="ppc-header">
                    <div>
                        <h1 class="ppc-h1">Start Tenancy</h1>
                        <div class="ppc-muted">This property already has an active tenancy.</div>
                    </div>
                </header>

                <section class="ppc-card">
                    <p>
                        <strong><?php echo esc_html($property_title); ?></strong> already has a <strong>Current</strong> tenancy.
                        End it first, or view/edit it.
                    </p>

                    <div class="ppc-actions">
                        <?php echo ppc_btn('View Current Tenancy', $view_url); ?>
                        <a class="ppc-btn ppc-btn--danger" href="<?php echo esc_url($end_url); ?>">End Current Tenancy</a>
                    </div>
                </section>
            </div>
            <?php
            return ob_get_clean();
        }
    }

    ob_start();

    echo '<h1 class="ppc-h1">' . esc_html($mode === 'edit' ? 'Edit Tenancy' : 'Start Tenancy') . '</h1>';

    acf_form([
        'post_id' => $post_id,
        'new_post' => [
            'post_type'   => 'ppm_tenancy',
            'post_status' => 'publish',
        ],
        'field_groups' => ['group_ppc_tenancy_fields'],
        'submit_value' => $mode === 'edit' ? 'Save Tenancy' : 'Create Tenancy',
        'return'       => ppc_portal_url('tenancies'),
    ]);

    $html = ob_get_clean();

    // Remove filters after rendering (avoid affecting other forms)
    if ($filter_tenant) remove_filter('acf/prepare_field/name=tenancy_tenant', $filter_tenant, 20);
    if ($filter_property) remove_filter('acf/prepare_field/name=tenancy_property', $filter_property, 20);

    return $html;
});

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

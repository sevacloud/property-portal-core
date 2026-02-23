<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('ppc_property_page_url')) {
    function ppc_property_page_url(int $id): string {
        return add_query_arg(['id' => $id], ppc_portal_url('property'));
    }
}

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
                                <td class="ppc-td">
                                    <a class="ppc-link" href="<?php echo esc_url(ppc_property_page_url((int) $p->ID)); ?>">
                                        <?php echo esc_html($p->post_title ?: '-'); ?>
                                    </a>
                                </td>
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

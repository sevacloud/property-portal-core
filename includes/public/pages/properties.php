<?php
if (!defined('ABSPATH')) exit;

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
                <div class="ppc-stack">
                    <?php foreach ($properties as $p): ?>
                        <?php
                        $code = function_exists('get_field') ? (string) get_field('property_code', (int)$p->ID) : '';
                        $region = function_exists('get_field') ? (string) get_field('property_region', (int)$p->ID) : '';
                        $status = function_exists('get_field') ? (string) get_field('property_status', (int)$p->ID) : '';
                        $manager = function_exists('get_field') ? get_field('property_manager', (int)$p->ID) : '';
                        $main_photo = function_exists('get_field') ? get_field('property_main_photo', (int)$p->ID) : null;
                        
                        $managed_by = '';
                        if (is_string($manager) && $manager !== '') $managed_by = $manager;
                        if (is_array($manager) && !empty($manager['display_name'])) $managed_by = (string) $manager['display_name'];
                        if (is_object($manager) && !empty($manager->display_name)) $managed_by = (string) $manager->display_name;
                        if (is_numeric($manager) && (int) $manager > 0) {
                            $m = get_user_by('id', (int) $manager);
                            if ($m && !empty($m->display_name)) $managed_by = (string) $m->display_name;
                        }
                        
                        $main_photo_id = 0;
                        if (is_array($main_photo) && !empty($main_photo['ID'])) $main_photo_id = (int) $main_photo['ID'];
                        if (is_numeric($main_photo)) $main_photo_id = (int) $main_photo;
                        
                        // Get current tenant
                        $current_tenancy_id = function_exists('ppc_get_current_tenancy_id_for_property') ? ppc_get_current_tenancy_id_for_property((int)$p->ID) : 0;
                        $current_tenant = '';
                        if ($current_tenancy_id > 0) {
                            $tenant_id = function_exists('get_field') ? (int) get_field('tenancy_tenant', $current_tenancy_id) : 0;
                            $current_tenant = $tenant_id ? (get_the_title($tenant_id) ?: '') : '';
                        }
                        ?>
                        <a class="ppc-card" href="<?php echo esc_url(ppc_page_url('property', (int)$p->ID)); ?>" style="text-decoration: none; color: inherit; display: block;">
                            <div class="ppc-property-details-grid">
                                <div class="ppc-property-details-grid__media">
                                    <?php if ($main_photo_id > 0): ?>
                                        <?php echo wp_get_attachment_image($main_photo_id, 'medium', false, ['class' => 'ppc-property-image']); ?>
                                    <?php else: ?>
                                        <div style="width: 200px; height: 150px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #999; font-size: 13px;">No Image</div>
                                    <?php endif; ?>
                                </div>
                                <div class="ppc-property-details-grid__content">
                                    <h3 class="ppc-h3" style="margin-bottom: 8px;"><?php echo esc_html($p->post_title ?: '—'); ?></h3>
                                    <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; font-size: 14px;">
                                        <?php if ($code): ?>
                                            <div style="font-weight: 700;">Code:</div>
                                            <div><?php echo esc_html($code); ?></div>
                                        <?php endif; ?>
                                        <?php if ($region): ?>
                                            <div style="font-weight: 700;">Region:</div>
                                            <div><?php echo esc_html($region); ?></div>
                                        <?php endif; ?>
                                        <?php if ($status): ?>
                                            <div style="font-weight: 700;">Status:</div>
                                            <div><?php echo esc_html($status); ?></div>
                                        <?php endif; ?>
                                        <div style="font-weight: 700;">Tenant:</div>
                                        <div><?php echo esc_html($current_tenant ?: 'Vacant'); ?></div>
                                        <?php if ($managed_by): ?>
                                            <div style="font-weight: 700;">Manager:</div>
                                            <div><?php echo esc_html($managed_by); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php
    return ob_get_clean();
});

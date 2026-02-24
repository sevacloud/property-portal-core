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
    $category = (string) $get('repair_category');
    $related_void = $get('repair_related_void');
    $main_photo = $get('repair_main_photo');
    $owner = $get('repair_owner');
    $notes = (string) $get('repair_notes');
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
    $property_title = '';
    $property = $get('repair_property');
    if (is_object($property) && !empty($property->post_title)) {
        $property_title = (string) $property->post_title;
        $property_id = (int) $property->ID;
    } elseif (is_numeric($property) && (int) $property > 0) {
        $property_id = (int) $property;
        $property_title = get_the_title($property_id) ?: '';
    }

    // Get related void title
    $related_void_title = '';
    if (is_object($related_void) && !empty($related_void->post_title)) $related_void_title = (string) $related_void->post_title;
    if (is_numeric($related_void) && (int) $related_void > 0) $related_void_title = get_the_title((int) $related_void) ?: '';

    // Get current tenant details for the linked property
    $current_tenant_details = 'Vacant';
    if ($property_id > 0 && function_exists('ppc_get_current_tenancy_id_for_property')) {
        $current_tenancy_id = ppc_get_current_tenancy_id_for_property($property_id);
        if ($current_tenancy_id > 0) {
            $tenant_id = function_exists('get_field') ? (int) get_field('tenancy_tenant', $current_tenancy_id) : 0;
            if ($tenant_id > 0) {
                $tenant_name = get_the_title($tenant_id) ?: '';
                $tenant_phone = function_exists('get_field') ? (string) get_field('tenant_phone', $tenant_id) : '';
                $tenant_email = function_exists('get_field') ? (string) get_field('tenant_email', $tenant_id) : '';

                $tenant_parts = array_filter([$tenant_name, $tenant_phone, $tenant_email], function ($v) {
                    return (string) $v !== '';
                });
                $current_tenant_details = !empty($tenant_parts) ? implode(' | ', $tenant_parts) : 'Occupied';
            } else {
                $current_tenant_details = 'Occupied';
            }
        }
    }

    // Get main photo ID
    $main_photo_id = 0;
    if (is_array($main_photo) && !empty($main_photo['ID'])) $main_photo_id = (int) $main_photo['ID'];
    if (is_numeric($main_photo)) $main_photo_id = (int) $main_photo;

    ob_start(); ?>
    <div class="ppc-stack">
        <header class="ppc-resource-header">
            <div class="ppc-resource-header__top">
                <h1 class="ppc-h1"><?php echo esc_html($summary ?: (get_the_title($repair_id) ?: 'Repair')); ?></h1>

                <div class="ppc-actions ppc-resource-header__actions">
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
            <div class="ppc-resource-details-grid">
                <div class="ppc-resource-details-grid__media">
                    <?php if ($main_photo_id > 0): ?>
                        <?php echo wp_get_attachment_image($main_photo_id, 'large', false, ['class' => 'ppc-resource-image']); ?>
                    <?php else: ?>
                        <div style="width: 200px; height: 150px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #999; font-size: 13px;">No Image</div>
                    <?php endif; ?>
                </div>
                <div class="ppc-resource-details-grid__content">
                    <table class="ppc-resource-details-table">
                        <tbody>
                            <tr>
                                <th>Property</th>
                                <td>
                                    <?php if ($property_title): ?>
                                        <a class="ppc-link" href="<?php echo esc_url(ppc_page_url('property', (int) $property_id)); ?>">
                                            <?php echo esc_html($property_title); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($category): ?>
                                <tr>
                                    <th>Category</th>
                                    <td><?php echo esc_html($category); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($related_void_title): ?>
                                <tr>
                                    <th>Related Void</th>
                                    <td>
                                        <a class="ppc-link" href="<?php echo esc_url(ppc_page_url('void', (int) $related_void)); ?>">
                                            <?php echo esc_html($related_void_title); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Assigned Owner</th>
                                <td><?php echo esc_html($owner_name ?: 'Unassigned'); ?></td>
                            </tr>
                            <tr>
                                <th>Current Tenant</th>
                                <td><?php echo esc_html($current_tenant_details); ?></td>
                            </tr>
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
                            <?php if ($notes): ?>
                                <tr>
                                    <th>Notes</th>
                                    <td><?php echo esc_html($notes); ?></td>
                                </tr>
                            <?php endif; ?>
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
                </div>
            </div>
        </section>

        <section class="ppc-card">
            <h2 class="ppc-h2">Comments</h2>
            <?php
            // Enqueue mentions script
            wp_enqueue_script('ppc-mentions', plugin_dir_url(__FILE__) . '../../../assets/js/ppc-mentions.js', [], '1.0', true);
            wp_localize_script('ppc-mentions', 'ppcMentions', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ppc_mentions_nonce')
            ]);

            // Disable duplicate comment detection for repair comments
            add_filter('duplicate_comment_id', '__return_false');

            // Enable inline threaded reply behavior on shortcode-rendered page.
            if (comments_open($repair_id) && get_option('thread_comments')) {
                wp_enqueue_script('comment-reply');
            }

            // Fallback: force reply links to the portal repair URL (not CPT permalink).
            $reply_link_filter = function ($link, $args, $comment, $post) use ($repair_id) {
                if ((int) $post->ID !== (int) $repair_id) return $link;

                $target = add_query_arg([
                    'id' => (int) $repair_id,
                    'replytocom' => (int) $comment->comment_ID,
                ], ppc_portal_url('repair')) . '#respond';

                return preg_replace('/href=("|\')(.*?)\1/', 'href="' . esc_url($target) . '"', $link, 1);
            };
            add_filter('comment_reply_link', $reply_link_filter, 10, 4);

            // Enable comments for this post type
            $repair_post = get_post($repair_id);
            if ($repair_post) {
                // Temporarily override the global post for comments
                global $post;
                $original_post = $post;
                $post = $repair_post;
                setup_postdata($post);

                // Get and display existing comments
                $comments = get_comments([
                    'post_id' => $repair_id,
                    'status' => 'approve',
                    'order' => 'ASC'
                ]);

                if (!empty($comments)) {
                    echo '<div style="margin-bottom: 20px;">';
                    wp_list_comments([
                        'style' => 'div',
                        'short_ping' => true,
                        'avatar_size' => 32,
                    ], $comments);
                    echo '</div>';
                }

                // Display comment form
                comment_form([
                    'title_reply' => 'Add Comment',
                    'label_submit' => 'Post Comment',
                    'comment_field' => '<p class="comment-form-comment"><label for="comment">Comment</label><textarea id="comment" name="comment" cols="45" rows="4" required placeholder="Type @ to mention someone..."></textarea></p>',
                ]);

                // Restore original post
                $post = $original_post;
                if ($original_post) {
                    setup_postdata($post);
                } else {
                    wp_reset_postdata();
                }
            }

            remove_filter('comment_reply_link', $reply_link_filter, 10);
            ?>
        </section>
    </div>
    <?php
    return ob_get_clean();
});

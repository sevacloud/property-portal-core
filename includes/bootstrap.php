<?php
if (!defined('ABSPATH')) exit;

/**
 * Core system
 */
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/cpt.php';
require_once __DIR__ . '/core/roles.php';
require_once __DIR__ . '/core/scope.php';
require_once __DIR__ . '/core/validation.php';
require_once __DIR__ . '/public/components.php';
//require_once __DIR__ . '/core/acf-queries.php';

// Public (front-end) functionality
require_once __DIR__ . '/public/shortcodes.php';
require_once __DIR__ . '/public/portal-pages.php';
require_once __DIR__ . '/public/pages/properties.php';
require_once __DIR__ . '/public/pages/property.php';
require_once __DIR__ . '/public/pages/repair.php';
require_once __DIR__ . '/acf/acf-forms.php';

/**
 * Admin-only tools
 */
if (is_admin()) {
    require_once __DIR__ . '/admin/debug.php';
}

/**
 * ACF integration (only if active)
 */
if (function_exists('acf_add_local_field_group')) {
    require_once __DIR__ . '/acf/field-groups.php';
}

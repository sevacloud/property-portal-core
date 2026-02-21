<?php
/**
 * Plugin Name: Property Portal Core
 * Description: Core portal logic for staff property/void/repair management.
 * Version: 0.4.0
 * Author: Liamarjit @ Seva Cloud
 */

if (!defined('ABSPATH')) exit;

define('PPC_PATH', plugin_dir_path(__FILE__));
define('PPC_URL', plugin_dir_url(__FILE__));

require_once PPC_PATH . 'includes/bootstrap.php';
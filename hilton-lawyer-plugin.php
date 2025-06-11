<?php
/**
 * Plugin Name: Hilton Lawyer Scheduler & Invoicer
 * Description: Schedule meetings with optional auto-generated Microsoft Teams links, and send client/lawyer email notifications.
 * Version: 2.3.0
 * Author: Qwantum Technologies
 */

if (!defined('ABSPATH')) exit;

// Plugin paths
define('HILTON_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HILTON_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load functionality
require_once HILTON_PLUGIN_DIR . 'includes/install.php';
require_once HILTON_PLUGIN_DIR . 'includes/hooks.php';
require_once HILTON_PLUGIN_DIR . 'includes/email-functions.php';
require_once HILTON_PLUGIN_DIR . 'includes/graph-handler.php'; // ✅ Added to enable real Teams API

// Activation: Create DB tables
register_activation_hook(__FILE__, 'hilton_create_tables');

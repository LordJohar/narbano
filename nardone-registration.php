<?php
/**
 * Plugin Name: Nardone Registration
 * Description: User registration via mobile, OTP, and username for WooCommerce.
 * Author: Lord Johar
 * Version: 0.4.2
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Basic constants
define('NARDONE_PLUGIN_VERSION', '0.4.2');
define('NARDONE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NARDONE_OTP_EXPIRY', 3 * MINUTE_IN_SECONDS);

// Include core files
require_once NARDONE_PLUGIN_DIR . 'includes/core-functions.php';
require_once NARDONE_PLUGIN_DIR . 'includes/registration-handler.php';
require_once NARDONE_PLUGIN_DIR . 'includes/login-handler.php';
require_once NARDONE_PLUGIN_DIR . 'includes/checkout-redirect.php';
require_once NARDONE_PLUGIN_DIR . 'includes/checkout-handler.php';
require_once NARDONE_PLUGIN_DIR . 'includes/checkout-login.php';
require_once NARDONE_PLUGIN_DIR . 'includes/admin-settings.php';
require_once NARDONE_PLUGIN_DIR . 'includes/frontend-assets.php';
require_once NARDONE_PLUGIN_DIR . 'includes/ajax-otp.php';

// Initialize plugin
add_action('plugins_loaded', 'nardone_plugin_init');
function nardone_plugin_init() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'nardone_woocommerce_notice');
        return;
    }
}

function nardone_woocommerce_notice() {
    echo '<div class="notice notice-error"><p>';
    echo 'افزونه Nardone Registration نیاز به ووکامرس فعال دارد.';
    echo '</p></div>';
}
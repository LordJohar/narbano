<?php
/**
 * Plugin Name: Nardone Registration
 * Description: User registration via mobile, OTP, and username for WooCommerce.
 * Author: Lord Johar
 * Version: 0.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

define( 'NARDONE_PLUGIN_VERSION', '0.3.1' );
define( 'NARDONE_PLUGIN_FILE', __FILE__ );
define( 'NARDONE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NARDONE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once NARDONE_PLUGIN_DIR . 'includes/constants.php';
require_once NARDONE_PLUGIN_DIR . 'includes/helpers.php';
require_once NARDONE_PLUGIN_DIR . 'includes/dependencies.php';
require_once NARDONE_PLUGIN_DIR . 'includes/admin-settings.php';
require_once NARDONE_PLUGIN_DIR . 'includes/registration-form.php';
require_once NARDONE_PLUGIN_DIR . 'includes/customer-data.php';
require_once NARDONE_PLUGIN_DIR . 'includes/validation.php';
require_once NARDONE_PLUGIN_DIR . 'includes/frontend-scripts.php';
require_once NARDONE_PLUGIN_DIR . 'includes/ajax-otp.php';
require_once NARDONE_PLUGIN_DIR . 'includes/password-policy.php';
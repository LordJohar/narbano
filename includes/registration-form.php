<?php
/**
 * Registration form fields and display tweaks.
 * Custom template at templates/myaccount/form-register.php handles the actual form rendering.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

/**
 * Placeholder function for compatibility with other hooks.
 */
function nardone_add_registration_fields() {
    // Fields are rendered in the custom template (templates/myaccount/form-register.php)
    return;
}
add_action( 'woocommerce_register_form', 'nardone_add_registration_fields' );


/**
 * CSS for OTP form styling.
 */
function nardone_registration_form_css() {
    if ( is_account_page() ) {
        echo '<style>
        /* OTP Input and Button in Row */
        .nardone-otp-row {
            display: flex;
            gap: 8px;
            align-items: center;
            width: 100%;
        }
        .nardone-otp-row #reg_nardone_otp_code {
            width: auto !important;
            flex: 1 1 auto;
            min-width: 0;
        }
        .nardone-otp-row #nardone_send_otp_btn {
            white-space: nowrap;
            padding: 10px 20px;
            height: 40px;
            flex-shrink: 0;
        }
        </style>';
    }
}
add_action( 'wp_head', 'nardone_registration_form_css' );

/**
 * Force WooCommerce to use plugin template for registration (removes password/email fields entirely).
 */
function nardone_locate_wc_template( $template, $template_name, $template_path ) {
    if ( 'myaccount/form-register.php' === $template_name ) {
        $plugin_path = trailingslashit( NARDONE_PLUGIN_DIR . 'templates' );
        $candidate   = $plugin_path . $template_name;

        if ( file_exists( $candidate ) ) {
            return $candidate;
        }
    }

    return $template;
}
add_filter( 'woocommerce_locate_template', 'nardone_locate_wc_template', 10, 3 );

/**
 * Force WooCommerce to auto-generate passwords (never require user input).
 */
add_filter( 'woocommerce_registration_generate_password', '__return_true' );

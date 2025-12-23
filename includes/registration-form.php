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
    // No extra fields here because the custom template renders all fields.
    // Placeholder kept for compatibility; do not remove.
}
add_action( 'woocommerce_register_form', 'nardone_add_registration_fields' );

/**
 * CSS for OTP form styling and aggressive password field hiding.
 */
function nardone_registration_form_css() {
    if ( is_account_page() ) {
        echo '<style>
        /* OTP Input and Button in Row */
        .nardone-otp-row {
            display: flex;
            gap: 8px;
            align-items: center;
            width: 100% !important;
            max-width: 100% !important;
            flex-wrap: nowrap;
        }
        .nardone-otp-row input.input-text,
        .nardone-otp-row button {
            width: 100% !important;
            max-width: 100% !important;
        }
        .woocommerce-form-register .form-row,
        .woocommerce-form-register .form-row-wide {
            width: 100% !important;
            max-width: 100% !important;
            float: none !important;
        }
        .woocommerce-form-register .form-row-wide .input-text,
        .woocommerce-form-register .form-row .input-text {
            width: 100% !important;
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
        /* Make OTP form row span full width */
        .woocommerce-form-register .form-row-wide {
            width: 100% !important;
            max-width: 100% !important;
            clear: both;
        }
        
        /* Hide all password fields - AGGRESSIVE */
        input[name="account_password"],
        input[name="account_password-2"],
        input[id*="password"],
        input[type="password"],
        label[for*="password"],
        label[for*="account_password"],
        .woocommerce-account .woocommerce-form-row--wide input[type="password"],
        #password_strength,
        .password-strength,
        .woocommerce-address-fields .form-row .password-input-wrapper,
        .woocommerce form.register .form-row input[type="password"],
        .woocommerce form.register .form-row label[for*="password"] {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
        }
        </style>';
    }
}
add_action( 'wp_head', 'nardone_registration_form_css' );

/**
 * Force WooCommerce to use plugin template for registration.
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
 * Force WooCommerce to auto-generate passwords.
 */
add_filter( 'woocommerce_registration_generate_password', '__return_true' );

/**
 * Remove password field from registration form via woocommerce_registration_form_fields.
 */
function nardone_remove_password_from_fields( $fields ) {
    if ( isset( $fields['account_password'] ) ) {
        unset( $fields['account_password'] );
    }
    if ( isset( $fields['account_password-2'] ) ) {
        unset( $fields['account_password-2'] );
    }
    return $fields;
}
add_filter( 'woocommerce_registration_form_fields', 'nardone_remove_password_from_fields' );

/**
 * Start output buffering to catch and filter password fields from HTML.
 */
function nardone_buffer_registration_form_start() {
    if ( is_account_page() && ! is_user_logged_in() ) {
        ob_start( 'nardone_filter_password_from_output' );
    }
}
add_action( 'wp_footer', 'nardone_buffer_registration_form_start', 1 );

/**
 * Filter password fields from buffered output.
 */
function nardone_filter_password_from_output( $html ) {
    // Remove password input fields by name
    $html = preg_replace( '/<input[^>]*name=["\']account_password["\'][^>]*>/i', '', $html );
    $html = preg_replace( '/<input[^>]*name=["\']account_password-2["\'][^>]*>/i', '', $html );
    $html = preg_replace( '/<input[^>]*id=["\']reg_password["\'][^>]*>/i', '', $html );
    $html = preg_replace( '/<input[^>]*id=["\']reg_password2["\'][^>]*>/i', '', $html );
    
    // Remove password labels
    $html = preg_replace( '/<label[^>]*for=["\']reg_password["\'][^>]*>.*?<\/label>/is', '', $html );
    $html = preg_replace( '/<label[^>]*for=["\']account_password["\'][^>]*>.*?<\/label>/is', '', $html );
    $html = preg_replace( '/<label[^>]*for=["\']account_password-2["\'][^>]*>.*?<\/label>/is', '', $html );
    
    // Remove password strength meter
    $html = preg_replace( '/<div[^>]*id=["\']password_strength["\'][^>]*>.*?<\/div>/is', '', $html );
    $html = preg_replace( '/<div[^>]*class=["\']password-strength["\'][^>]*>.*?<\/div>/is', '', $html );
    
    // Remove password-related form rows entirely
    $html = preg_replace( '/<p[^>]*class=["\']form-row[^"]*["\'][^>]*>[\s\n]*<label[^>]*for=["\'].*?password["\'][^>]*>.*?<\/label>.*?<\/p>/is', '', $html );
    
    return $html;
}

/**
 * End output buffering.
 */
function nardone_buffer_registration_form_end() {
    if ( is_account_page() && ! is_user_logged_in() ) {
        ob_end_flush();
    }
}
add_action( 'wp_footer', 'nardone_buffer_registration_form_end', 999 );


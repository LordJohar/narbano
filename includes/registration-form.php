<?php
/**
 * Registration form fields and display tweaks.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

/**
 * Add custom fields to WooCommerce registration form.
 */
function nardone_add_registration_fields() {
    ?>
    <p class="form-row form-row-first">
        <label for="reg_billing_first_name"><?php esc_html_e( 'First name', 'nardone' ); ?>&nbsp;<span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php echo isset( $_POST['billing_first_name'] ) ? esc_attr( wp_unslash( $_POST['billing_first_name'] ) ) : ''; ?>" />
    </p>

    <p class="form-row form-row-last">
        <label for="reg_billing_last_name"><?php esc_html_e( 'Last name', 'nardone' ); ?>&nbsp;<span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php echo isset( $_POST['billing_last_name'] ) ? esc_attr( wp_unslash( $_POST['billing_last_name'] ) ) : ''; ?>" />
    </p>

    <p class="form-row form-row-wide">
        <label for="reg_billing_phone"><?php esc_html_e( 'Mobile number', 'nardone' ); ?>&nbsp;<span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_phone" id="reg_billing_phone" value="<?php echo isset( $_POST['billing_phone'] ) ? esc_attr( wp_unslash( $_POST['billing_phone'] ) ) : ''; ?>" placeholder="09121234567" />
    </p>

    <p class="form-row form-row-wide">
        <label for="reg_nardone_otp_code"><?php esc_html_e( 'Verification code (OTP)', 'nardone' ); ?>&nbsp;<span class="required">*</span></label>
        <div class="nardone-otp-row">
            <input type="text" class="input-text" name="nardone_otp_code" id="reg_nardone_otp_code" value="" />
            <button type="button" class="button" id="nardone_send_otp_btn">
                <?php esc_html_e( 'دریافت کد', 'nardone' ); ?>
            </button>
        </div>
        <small class="description"><?php esc_html_e( 'After you receive the code, enter it above.', 'nardone' ); ?></small>
    </p>

    <div style="clear:both"></div>
    <?php
}
add_action( 'woocommerce_register_form', 'nardone_add_registration_fields' );

/**
 * Hide email and password fields on My Account registration form (OTP flow uses phone).
 */
function nardone_hide_email_password_field_css() {
    if ( is_account_page() ) {
        echo '<style>
        /* Hide email field - all variations */
        .woocommerce form.register input#reg_email,
        .woocommerce form.register label[for="reg_email"],
        input#reg_email,
        label[for="reg_email"] {
            display: none !important;
        }
        
        /* Hide password fields - all variations */
        .woocommerce form.register input#reg_password,
        .woocommerce form.register input#reg_password2,
        .woocommerce form.register label[for="reg_password"],
        .woocommerce form.register label[for="reg_password2"],
        input#reg_password,
        input#reg_password2,
        label[for="reg_password"],
        label[for="reg_password2"],
        #woocommerce-password-strength,
        .woocommerce-password-strength,
        #password_strength,
        .password-strength {
            display: none !important;
        }
        
        /* Hide password strength meter and related elements */
        .woocommerce form.register .form-row.form-row-wide:has(#reg_password),
        .woocommerce form.register .form-row.form-row-wide:has(#reg_password2),
        .woocommerce form.register .form-row:has(#reg_password),
        .woocommerce form.register .form-row:has(#reg_password2) {
            display: none !important;
        }
        
        /* OTP Input and Button in Row */
        .nardone-otp-row {
            display: flex;
            gap: 8px;
            align-items: center;
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
add_action( 'wp_head', 'nardone_hide_email_password_field_css' );

/**
 * Last-resort output filter: strip password field HTML from the rendered form in case theme overrides add it back.
 */
function nardone_strip_password_fields_from_output( $form ) {
    // Remove common password input patterns.
    $patterns = array(
        '/<p[^>]*class="[^"]*form-row[^>]*">[^<]*<label[^>]*for="reg_password"[^>]*>.*?<\/label>.*?<input[^>]*id="reg_password"[^>]*>.*?<\/p>/is',
        '/<p[^>]*class="[^"]*form-row[^>]*">[^<]*<label[^>]*for="reg_password2"[^>]*>.*?<\/label>.*?<input[^>]*id="reg_password2"[^>]*>.*?<\/p>/is',
        '/<label[^>]*for="reg_password"[^>]*>.*?<\/label>/is',
        '/<input[^>]*id="reg_password"[^>]*>/is',
        '/<label[^>]*for="reg_password2"[^>]*>.*?<\/label>/is',
        '/<input[^>]*id="reg_password2"[^>]*>/is',
        '/<div[^>]*class="[^"]*password[^>]*strength[^>]*">.*?<\/div>/is',
    );

    return preg_replace( $patterns, '', $form );
}
add_filter( 'woocommerce_register_form', 'nardone_strip_password_fields_from_output', 999 );

/**
 * Suppress rendering of any password fields via WooCommerce form_field filter (catches theme overrides).
 */
function nardone_filter_password_form_field( $field, $key, $args, $value ) {
    $password_keys = array( 'account_password', 'account_password-2', 'password', 'reg_password', 'reg_password2' );

    if ( in_array( $key, $password_keys, true ) ) {
        return '';
    }

    return $field;
}
add_filter( 'woocommerce_form_field', 'nardone_filter_password_form_field', 9, 4 );

/**
 * Remove password field from WooCommerce registration form and force auto-generated password.
 */
function nardone_remove_password_field( $fields ) {
    if ( isset( $fields['account_password'] ) ) {
        unset( $fields['account_password'] );
    }

    return $fields;
}
add_filter( 'woocommerce_registration_form_fields', 'nardone_remove_password_field', 20, 1 );

// Force WooCommerce to generate passwords automatically (no user input shown).
add_filter( 'woocommerce_registration_generate_password', '__return_true' );

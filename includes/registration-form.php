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
        /* Hide email field */
        .woocommerce form.register p.form-row-wide label[for="reg_email"],
        .woocommerce form.register p.form-row-wide input#reg_email {
            display: none !important;
        }
        
        /* Hide password fields */
        .woocommerce form.register p.form-row-wide label[for="reg_password"],
        .woocommerce form.register p.form-row-wide input#reg_password,
        .woocommerce form.register p.form-row-wide label[for="reg_password2"],
        .woocommerce form.register p.form-row-wide input#reg_password2,
        .woocommerce form.register #password_strength,
        .woocommerce form.register .description {
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

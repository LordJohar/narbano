<?php
/**
 * Registration functionality
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add custom fields to registration form
 */
function nardone_add_registration_fields() {
    ?>
    <p class="form-row form-row-first">
        <label for="reg_billing_first_name">نام&nbsp;<span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php echo isset( $_POST['billing_first_name'] ) ? esc_attr( wp_unslash( $_POST['billing_first_name'] ) ) : ''; ?>" />
    </p>

    <p class="form-row form-row-last">
        <label for="reg_billing_last_name">نام خانوادگی&nbsp;<span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php echo isset( $_POST['billing_last_name'] ) ? esc_attr( wp_unslash( $_POST['billing_last_name'] ) ) : ''; ?>" />
    </p>

    <p class="form-row form-row-wide">
        <label for="reg_billing_phone">شماره موبایل&nbsp;<span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_phone" id="reg_billing_phone" value="<?php echo isset( $_POST['billing_phone'] ) ? esc_attr( wp_unslash( $_POST['billing_phone'] ) ) : ''; ?>" placeholder="مثال: 09121234567" />
    </p>

    <p class="form-row form-row-wide nardone-otp-row">
        <span class="nardone-otp-input-wrapper">
            <input type="text" class="input-text" name="nardone_otp_code" id="reg_nardone_otp_code" value="" maxlength="6" />
            <button type="button" class="button" id="nardone_send_otp_btn">
                دریافت کد تأیید
            </button>
        </span>
    </p>

    <p class="form-row form-row-wide nardone-ref-toggle-row">
        <a href="#" class="nardone-ref-toggle"><?php esc_html_e( 'I have a referrer', 'nardone' ); ?></a>
    </p>

    <div class="form-row form-row-wide nardone-referrer-field" style="display:none;">
        <label for="reg_nardone_referrer_phone"><?php esc_html_e( 'Referrer mobile (optional)', 'nardone' ); ?></label>
        <input type="text" class="input-text" name="nardone_referrer_phone" id="reg_nardone_referrer_phone" value="<?php echo isset( $_POST['nardone_referrer_phone'] ) ? esc_attr( wp_unslash( $_POST['nardone_referrer_phone'] ) ) : ''; ?>" placeholder="09121234567" />
        <small class="description"><?php esc_html_e( 'If someone referred you, enter their mobile number. Leave empty if none.', 'nardone' ); ?></small>
    </div>
    <?php
}
add_action( 'woocommerce_register_form', 'nardone_add_registration_fields' );

/**
 * Generate username and fake email
 */
function nardone_customize_new_customer_data( $data ) {
    // Generate username
    $data['user_login'] = nardone_generate_username();
    
    // Generate fake email if none provided
    if ( empty( $data['user_email'] ) && ! empty( $_POST['billing_phone'] ) ) {
        $phone = nardone_normalize_phone_digits( $_POST['billing_phone'] );
        if ( ! empty( $phone ) ) {
            $fake_email = 'user_' . $phone . '@noemail.nardone';
            $data['user_email'] = sanitize_email( $fake_email );
        }
    }
    
    return $data;
}
add_filter( 'woocommerce_new_customer_data', 'nardone_customize_new_customer_data', 10, 1 );

/**
 * Force fake email for registration
 */
function nardone_force_fake_email() {
    if ( is_admin() || is_user_logged_in() ) {
        return;
    }
    
    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        return;
    }
    
    // Check if it's a WooCommerce registration
    $is_registration = ! empty( $_POST['woocommerce-register-nonce'] ) || 
                       ( ! empty( $_POST['register'] ) && ! empty( $_POST['billing_phone'] ) );
    
    if ( $is_registration && empty( $_POST['email'] ) && ! empty( $_POST['billing_phone'] ) ) {
        $phone = nardone_normalize_phone_digits( $_POST['billing_phone'] );
        if ( ! empty( $phone ) ) {
            $fake_email = 'user_' . $phone . '_' . wp_rand( 1000, 9999 ) . '@noemail.nardone';
            $_POST['email'] = $fake_email;
            $_REQUEST['email'] = $fake_email;
        }
    }
}
add_action( 'init', 'nardone_force_fake_email', 1 );

/**
 * Validate registration fields
 */
function nardone_validate_registration( $errors, $username, $email ) {
    // Remove email errors
    if ( method_exists( $errors, 'remove' ) ) {
        $errors->remove( 'registration-error-email-required' );
        $errors->remove( 'registration-error-invalid-email' );
    }
    
    // Validate first name
    if ( empty( $_POST['billing_first_name'] ) ) {
        $errors->add( 'billing_first_name_error', 'لطفا نام خود را وارد کنید.' );
    }
    
    // Validate last name
    if ( empty( $_POST['billing_last_name'] ) ) {
        $errors->add( 'billing_last_name_error', 'لطفا نام خانوادگی خود را وارد کنید.' );
    }
    
    // Validate mobile
    if ( empty( $_POST['billing_phone'] ) ) {
        $errors->add( 'billing_phone_error', 'لطفا شماره موبایل خود را وارد کنید.' );
    } else {
        $phone = nardone_normalize_phone_digits( $_POST['billing_phone'] );
        
        if ( ! nardone_is_valid_mobile( $phone ) ) {
            $errors->add( 'billing_phone_format_error', 'شماره موبایل معتبر نیست. مثال: 09121234567' );
        } elseif ( nardone_mobile_exists( $phone ) ) {
            $errors->add( 'billing_phone_exists', 'با این شماره موبایل قبلا ثبت‌نام انجام شده است.' );
        }
    }
    
    // Validate OTP
    if ( empty( $_POST['nardone_otp_code'] ) ) {
        $errors->add( 'nardone_otp_code_error', 'لطفا کد تأیید را وارد کنید.' );
    } elseif ( ! empty( $_POST['billing_phone'] ) ) {
        $phone = nardone_normalize_phone_digits( $_POST['billing_phone'] );
        $otp_code = nardone_normalize_phone_digits( $_POST['nardone_otp_code'] );
        
        if ( ! preg_match( '/^[0-9]{6}$/', $otp_code ) ) {
            $errors->add( 'nardone_otp_code_format', 'کد تأیید باید ۶ رقم باشد.' );
        } else {
            $otp_key = 'nardone_otp_' . md5( $phone );
            $otp_data = get_transient( $otp_key );
            
            if ( ! $otp_data || ! is_array( $otp_data ) ) {
                $errors->add( 'nardone_otp_not_found', 'کد تأیید منقضی شده یا وجود ندارد.' );
            } elseif ( $otp_data['phone'] !== $phone ) {
                $errors->add( 'nardone_otp_phone_mismatch', 'کد تأیید برای این شماره نیست.' );
            } elseif ( time() > $otp_data['expires'] ) {
                $errors->add( 'nardone_otp_expired', 'کد تأیید منقضی شده است.' );
            } elseif ( $otp_data['code'] !== $otp_code ) {
                $errors->add( 'nardone_otp_wrong', 'کد تأیید صحیح نیست.' );
            } else {
                // Valid OTP - delete it
                delete_transient( $otp_key );
            }
        }
    }
    
    return $errors;
}
add_filter( 'woocommerce_registration_errors', 'nardone_validate_registration', 10, 3 );

/**
 * Save registration fields
 */
function nardone_save_registration_fields( $customer_id ) {
    // Save first name
    if ( isset( $_POST['billing_first_name'] ) ) {
        $first_name = sanitize_text_field( wp_unslash( $_POST['billing_first_name'] ) );
        update_user_meta( $customer_id, 'first_name', $first_name );
        update_user_meta( $customer_id, 'billing_first_name', $first_name );
    }
    
    // Save last name
    if ( isset( $_POST['billing_last_name'] ) ) {
        $last_name = sanitize_text_field( wp_unslash( $_POST['billing_last_name'] ) );
        update_user_meta( $customer_id, 'last_name', $last_name );
        update_user_meta( $customer_id, 'billing_last_name', $last_name );
    }
    
    // Save mobile
    if ( isset( $_POST['billing_phone'] ) ) {
        $phone = nardone_normalize_phone_digits( $_POST['billing_phone'] );
        update_user_meta( $customer_id, 'billing_phone', $phone );
    }
    
    // Mark mobile as verified
    update_user_meta( $customer_id, 'nardone_mobile_verified', 1 );
}
add_action( 'woocommerce_created_customer', 'nardone_save_registration_fields', 10, 1 );

/**
 * Hide email field in registration
 */
function nardone_hide_email_field() {
    if ( is_account_page() ) {
        ?>
        <style>
        /* مخفی کردن فیلد ایمیل */
        .woocommerce form.register p.form-row-wide label[for="reg_email"],
        .woocommerce form.register p.form-row-wide input#reg_email {
            display: none !important;
        }

        /* ردیف کد تأیید */
        .woocommerce form.register .nardone-otp-input-wrapper {
            display: flex;
            flex-direction: row;   /* همیشه افقی، حتی در موبایل */
            gap: 8px;
            align-items: center;
        }

        .woocommerce form.register .nardone-otp-input-wrapper .input-text {
            flex: 1;
            margin-bottom: 0;
        }

        .woocommerce form.register #nardone_send_otp_btn {
            flex: 0 0 auto;
            margin-top: 0 !important;
            width: auto !important;      /* جلوگیری از فول‌ویدث شدن در موبایل */
            display: inline-flex !important;
            white-space: nowrap;
        }
        </style>
        <?php
    }
}
add_action( 'wp_head', 'nardone_hide_email_field' );
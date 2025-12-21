<?php
/**
 * Registration Form override for Nardone OTP flow.
 * Phone + OTP only. Password is auto-generated server-side.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_register_form_start' );

?>

<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >

    <p class="form-row form-row-first">
        <label for="reg_billing_first_name"><?php esc_html_e( 'First name', 'nardone' ); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" autocomplete="given-name" value="<?php echo isset( $_POST['billing_first_name'] ) ? esc_attr( wp_unslash( $_POST['billing_first_name'] ) ) : ''; ?>" />
    </p>

    <p class="form-row form-row-last">
        <label for="reg_billing_last_name"><?php esc_html_e( 'Last name', 'nardone' ); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" autocomplete="family-name" value="<?php echo isset( $_POST['billing_last_name'] ) ? esc_attr( wp_unslash( $_POST['billing_last_name'] ) ) : ''; ?>" />
    </p>

    <p class="form-row form-row-wide">
        <label for="reg_billing_phone"><?php esc_html_e( 'Mobile number', 'nardone' ); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_phone" id="reg_billing_phone" autocomplete="tel" placeholder="09121234567" value="<?php echo isset( $_POST['billing_phone'] ) ? esc_attr( wp_unslash( $_POST['billing_phone'] ) ) : ''; ?>" />
    </p>

    <p class="form-row form-row-wide">
        <label for="reg_nardone_otp_code"><?php esc_html_e( 'Verification code (OTP)', 'nardone' ); ?> <span class="required">*</span></label>
        <div class="nardone-otp-row">
            <input type="text" class="input-text" name="nardone_otp_code" id="reg_nardone_otp_code" autocomplete="one-time-code" />
            <button type="button" class="button" id="nardone_send_otp_btn"><?php esc_html_e( 'دریافت کد', 'nardone' ); ?></button>
        </div>
        <small class="description"><?php esc_html_e( 'After you receive the code, enter it above.', 'nardone' ); ?></small>
    </p>

    <div class="clear"></div>

    <p class="woocommerce-FormRow form-row">
        <?php wp_nonce_field( 'woocommerce-register', '_wpnonce', false ); ?>
        <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ); ?>" />
        <button type="submit" class="woocommerce-Button button" name="register" value="<?php esc_attr_e( 'Register', 'nardone' ); ?>"><?php esc_html_e( 'Register', 'nardone' ); ?></button>
    </p>

</form>

<?php do_action( 'woocommerce_register_form_end' ); ?>

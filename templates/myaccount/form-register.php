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
        <div class="nardone-otp-row">
            <input type="text" class="input-text" name="nardone_otp_code" id="reg_nardone_otp_code" autocomplete="one-time-code" placeholder="<?php esc_attr_e( 'کد تأیید', 'nardone' ); ?>" />
            <button type="button" class="button" id="nardone_send_otp_btn"><?php esc_html_e( 'دریافت کد', 'nardone' ); ?></button>
        </div>
    </p>

    <p class="form-row form-row-wide nardone-ref-toggle-row">
        <a href="#" class="nardone-ref-toggle"><?php esc_html_e( 'I have a referrer', 'nardone' ); ?></a>
    </p>

    <div class="form-row form-row-wide nardone-referrer-field" style="display:none;">
        <label for="reg_nardone_referrer_phone"><?php esc_html_e( 'Referrer mobile (optional)', 'nardone' ); ?></label>
        <input type="text" class="input-text" name="nardone_referrer_phone" id="reg_nardone_referrer_phone" autocomplete="tel" placeholder="09121234567" value="<?php echo isset( $_POST['nardone_referrer_phone'] ) ? esc_attr( wp_unslash( $_POST['nardone_referrer_phone'] ) ) : ''; ?>" />
        <small class="description"><?php esc_html_e( 'If someone referred you, enter their mobile number. We will show their masked name (e.g., م.پناهی). Leave empty if none.', 'nardone' ); ?></small>
    </div>

    <div class="clear"></div>

    <p class="woocommerce-FormRow form-row">
        <?php wp_nonce_field( 'woocommerce-register', '_wpnonce', false ); ?>
        <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ); ?>" />
        <button type="submit" class="woocommerce-Button button" name="register" value="<?php esc_attr_e( 'Register', 'nardone' ); ?>"><?php esc_html_e( 'Register', 'nardone' ); ?></button>
    </p>

</form>

<?php do_action( 'woocommerce_register_form_end' ); ?>

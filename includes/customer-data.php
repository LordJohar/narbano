<?php
/**
 * Customer data customization and saving.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

/**
 * Customize new customer data: auto username, fake email, and auto-generated password.
 *
 * @param array $data WooCommerce new customer data.
 * @return array
 */
function nardone_customize_new_customer_data( $data ) {
    // Custom username
    $data['user_login'] = nardone_generate_username();

    // Create a synthetic email based on phone if empty
    if ( empty( $data['user_email'] ) && ! empty( $_POST['billing_phone'] ) ) {
        $phone_digits = preg_replace( '/\D/', '', nardone_normalize_phone_digits( $_POST['billing_phone'] ) );
        if ( empty( $phone_digits ) ) {
            $phone_digits = wp_rand( 10000000, 99999999 );
        }

        $fake_email         = 'u' . $phone_digits . '-' . wp_rand( 1000, 9999 ) . '@noemail.nardone';
        $data['user_email'] = sanitize_email( $fake_email );
    }

    // Auto-generate a strong random password since registration doesn't require user input
    if ( empty( $data['user_pass'] ) ) {
        $data['user_pass'] = wp_generate_password( 16, true, true );
    }

    return $data;
}
add_filter( 'woocommerce_new_customer_data', 'nardone_customize_new_customer_data', 10, 1 );

/**
 * Ensure a fake email is injected into POST when WooCommerce registration is submitted without email.
 */
function nardone_force_fake_email_on_registration() {
    $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : '';
    if ( 'POST' !== $method ) {
        return;
    }

    // Only front-end
    if ( is_admin() ) {
        return;
    }

    // Skip if logged in
    if ( is_user_logged_in() ) {
        return;
    }

    // Only when WC registration form is submitted
    $has_wc_register = ! empty( $_POST['woocommerce-register-nonce'] ) || ! empty( $_POST['register'] );
    if ( ! $has_wc_register ) {
        return;
    }

    if ( ! empty( $_POST['email'] ) ) {
        return;
    }

    if ( empty( $_POST['billing_phone'] ) ) {
        return;
    }

    $phone_digits = preg_replace( '/\D/', '', nardone_normalize_phone_digits( $_POST['billing_phone'] ) );
    if ( empty( $phone_digits ) ) {
        $phone_digits = (string) wp_rand( 10000000, 99999999 );
    }

    $fake_email = 'u' . $phone_digits . '-' . wp_rand( 1000, 9999 ) . '@noemail.nardone';
    $fake_email = sanitize_email( $fake_email );

    $_POST['email']    = $fake_email;
    $_REQUEST['email'] = $fake_email;
}
add_action( 'init', 'nardone_force_fake_email_on_registration', 1 );

/**
 * Save custom registration fields after customer creation.
 *
 * @param int $customer_id User ID.
 */
function nardone_save_registration_fields( $customer_id ) {
    if ( isset( $_POST['billing_first_name'] ) ) {
        $first_name = sanitize_text_field( wp_unslash( $_POST['billing_first_name'] ) );
        update_user_meta( $customer_id, 'first_name', $first_name );
        update_user_meta( $customer_id, 'billing_first_name', $first_name );
    }

    if ( isset( $_POST['billing_last_name'] ) ) {
        $last_name = sanitize_text_field( wp_unslash( $_POST['billing_last_name'] ) );
        update_user_meta( $customer_id, 'last_name', $last_name );
        update_user_meta( $customer_id, 'billing_last_name', $last_name );
    }

    if ( isset( $_POST['billing_phone'] ) ) {
        $phone = nardone_normalize_phone_digits( $_POST['billing_phone'] );
        update_user_meta( $customer_id, 'billing_phone', $phone );
    }

    // Optional referrer data.
    $ref_phone_norm = isset( $_POST['nardone_referrer_phone_norm'] ) ? nardone_normalize_phone_digits( $_POST['nardone_referrer_phone_norm'] ) : '';
    $ref_user_id    = isset( $_POST['nardone_referrer_user_id'] ) ? absint( $_POST['nardone_referrer_user_id'] ) : 0;
    $ref_name_mask  = isset( $_POST['nardone_referrer_name_mask'] ) ? sanitize_text_field( wp_unslash( $_POST['nardone_referrer_name_mask'] ) ) : '';

    if ( ! empty( $ref_phone_norm ) ) {
        update_user_meta( $customer_id, 'nardone_referrer_phone', $ref_phone_norm );
    }

    if ( $ref_user_id > 0 ) {
        update_user_meta( $customer_id, 'nardone_referrer_user', $ref_user_id );
    }

    if ( ! empty( $ref_name_mask ) ) {
        update_user_meta( $customer_id, 'nardone_referrer_name_mask', $ref_name_mask );
    }

    // Mark phone as verified after successful OTP.
    update_user_meta( $customer_id, 'nardone_mobile_verified', 1 );
}
add_action( 'woocommerce_created_customer', 'nardone_save_registration_fields', 10, 1 );

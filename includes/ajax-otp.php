<?php
/**
 * AJAX handler for sending OTP.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}
/**
 * Send OTP via AJAX
 */
function nardone_send_otp_ajax() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'nardone_send_otp' ) ) {
        wp_send_json_error( array( 'message' => 'خطای امنیتی' ) );
    }
    
    // Get phone
    $phone = nardone_normalize_phone_digits( $_POST['phone'] ?? '' );
    
    if ( ! nardone_is_valid_mobile( $phone ) ) {
        wp_send_json_error( array( 'message' => 'شماره موبایل معتبر نیست' ) );
    }
    
    // Check rate limit
    $otp_key = 'nardone_otp_' . md5( $phone );
    $existing = get_transient( $otp_key );
    
    if ( $existing && isset( $existing['last_sent'] ) && ( time() - $existing['last_sent'] ) < 60 ) {
        wp_send_json_error( array( 'message' => 'لطفا ۱ دقیقه صبر کنید' ) );
    }
    
    // Generate OTP
    $otp_code = str_pad( wp_rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
    
    // Save OTP data
    $otp_data = array(
        'code'      => $otp_code,
        'phone'     => $phone,
        'expires'   => time() + NARDONE_OTP_EXPIRY,
        'last_sent' => time()
    );
    
    set_transient( $otp_key, $otp_data, NARDONE_OTP_EXPIRY + 60 );
    
    // Get SMS settings
    $api_key       = get_option( 'nardone_otp_api_key', '' );
    $pattern_code  = get_option( 'nardone_otp_pattern_code', '' );
    $from_number   = get_option( 'nardone_otp_from_number', '' );
    $pattern_param = get_option( 'nardone_otp_pattern_param', 'otp_code' );
    
    // Send SMS if settings exist
    if ( ! empty( $api_key ) && ! empty( $pattern_code ) && ! empty( $from_number ) ) {
        $body = array(
            'sending_type' => 'pattern',
            'from_number'  => $from_number,
            'code'         => $pattern_code,
            'recipients'   => array( $phone ),
            'params'       => array( $pattern_param => $otp_code ),
        );
        
        $response = wp_remote_post( 'https://edge.ippanel.com/v1/api/send', array(
            'headers' => array(
                'Authorization' => $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 15,
        ) );
        
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => 'خطا در ارسال پیامک' ) );
        }
        
        $status = wp_remote_retrieve_response_code( $response );
        if ( $status < 200 || $status >= 300 ) {
            wp_send_json_error( array( 'message' => 'خطا در سرویس پیامک' ) );
        }
    }
    
    wp_send_json_success( array( 'message' => 'کد تأیید ارسال شد' ) );
}
add_action( 'wp_ajax_nardone_send_otp', 'nardone_send_otp_ajax' );
add_action( 'wp_ajax_nopriv_nardone_send_otp', 'nardone_send_otp_ajax' );

/**
 * Check referrer by phone via AJAX.
 */
function nardone_check_referrer_ajax() {
    // Verify nonce (reuse OTP nonce for simplicity)
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'nardone_send_otp' ) ) {
        wp_send_json_error( array( 'message' => __( 'خطای امنیتی', 'nardone' ) ) );
    }

    $phone = nardone_normalize_phone_digits( $_POST['phone'] ?? '' );

    if ( ! preg_match( '/^09[0-9]{9}$/', $phone ) ) {
        wp_send_json_error( array( 'message' => __( 'شماره معتبر نیست. مثال: 09121234567', 'nardone' ) ) );
    }

    $user = nardone_find_user_by_billing_phone( $phone );

    if ( ! $user instanceof WP_User ) {
        wp_send_json_error( array( 'message' => __( 'کاربری با این شماره یافت نشد.', 'nardone' ) ) );
    }

    $mask = nardone_mask_user_name( $user );

    wp_send_json_success( array(
        'name_mask' => $mask,
        'user_id'   => $user->ID,
        'phone'     => $phone,
    ) );
}
add_action( 'wp_ajax_nardone_check_referrer', 'nardone_check_referrer_ajax' );
add_action( 'wp_ajax_nopriv_nardone_check_referrer', 'nardone_check_referrer_ajax' );
<?php
/**
 * AJAX handler for sending OTP.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

/**
 * Send OTP to the provided mobile number using panel settings.
 */
function nardone_send_otp_ajax() {
    if ( ! isset( $_POST['nonce'], $_POST['phone'] ) ) {
        wp_send_json_error( array(
            'message' => __( 'Invalid request.', 'nardone' ),
        ) );
    }

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'nardone_send_otp' ) ) {
        wp_send_json_error( array(
            'message' => __( 'Security check failed. Please refresh the page.', 'nardone' ),
        ) );
    }

    $api_key       = trim( (string) get_option( NARDONE_OPT_API_KEY, '' ) );
    $pattern_code  = trim( (string) get_option( NARDONE_OPT_PATTERN_CODE, '' ) );
    $from_number   = trim( (string) get_option( NARDONE_OPT_FROM_NUMBER, '' ) );
    $pattern_param = trim( (string) get_option( NARDONE_OPT_PATTERN_PARAM, 'otp_code' ) );

    if ( empty( $api_key ) || empty( $pattern_code ) || empty( $from_number ) || empty( $pattern_param ) ) {
        wp_send_json_error( array(
            'message' => __( 'OTP settings are incomplete. Please contact site admin.', 'nardone' ),
        ) );
    }

    $phone = nardone_normalize_phone_digits( $_POST['phone'] );

    if ( ! preg_match( '/^09[0-9]{9}$/', $phone ) ) {
        wp_send_json_error( array(
            'message' => __( 'Invalid mobile number. Example: 09121234567', 'nardone' ),
        ) );
    }

    $otp_key  = 'nardone_otp_' . md5( $phone );
    $existing = get_transient( $otp_key );

    if ( $existing && isset( $existing['last_sent'] ) && ( time() - (int) $existing['last_sent'] ) < 60 ) {
        wp_send_json_error( array(
            'message' => __( 'A code was recently sent. Please wait a bit and try again.', 'nardone' ),
        ) );
    }

    $otp_code = wp_rand( 100000, 999999 );

    $data = array(
        'code'      => (string) $otp_code,
        'phone'     => $phone,
        'expires'   => time() + NARDONE_OTP_EXPIRY,
        'last_sent' => time(),
    );
    set_transient( $otp_key, $data, NARDONE_OTP_EXPIRY + 60 );

    $body = array(
        'sending_type' => 'pattern',
        'from_number'  => $from_number,
        'code'         => $pattern_code,
        'recipients'   => array( $phone ),
        'params'       => array(
            $pattern_param => (string) $otp_code,
        ),
    );

    $response = wp_remote_post( NARDONE_OTP_API_URL, array(
        'headers' => array(
            'Authorization' => $api_key,
            'Content-Type'  => 'application/json',
        ),
        'body'    => wp_json_encode( $body ),
        'timeout' => 15,
    ) );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array(
            'message' => __( 'Could not reach SMS service. Please try again later.', 'nardone' ),
        ) );
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $resp_body   = wp_remote_retrieve_body( $response );
    $resp_json   = json_decode( $resp_body, true );

    if ( $status_code < 200 || $status_code >= 300 ) {
        $msg = __( 'SMS sending failed.', 'nardone' );
        if ( is_array( $resp_json ) && ! empty( $resp_json['message'] ) ) {
            $msg .= ' ' . $resp_json['message'];
        }

        wp_send_json_error( array(
            'message' => $msg,
        ) );
    }

    wp_send_json_success( array(
        'message' => __( 'Verification code sent successfully.', 'nardone' ),
    ) );
}
add_action( 'wp_ajax_nardone_send_otp',        'nardone_send_otp_ajax' );
add_action( 'wp_ajax_nopriv_nardone_send_otp', 'nardone_send_otp_ajax' );

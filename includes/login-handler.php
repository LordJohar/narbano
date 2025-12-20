<?php
/**
 * Login functionality
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Allow login with mobile number
 */
function nardone_mobile_login_auth( $user, $username, $password ) {
    if ( is_a( $user, 'WP_User' ) ) {
        return $user;
    }
    
    if ( empty( $username ) || empty( $password ) ) {
        return $user;
    }
    
    // Check if input is a mobile number
    $normalized = nardone_normalize_phone_digits( $username );
    
    if ( nardone_is_valid_mobile( $normalized ) ) {
        // Find user by mobile
        $users = get_users( array(
            'meta_key'   => 'billing_phone',
            'meta_value' => $normalized,
            'number'     => 1,
            'fields'     => 'ID'
        ) );
        
        if ( ! empty( $users ) ) {
            $user_id = $users[0];
            $user = get_user_by( 'id', $user_id );
            
            // Verify password
            if ( $user && wp_check_password( $password, $user->user_pass, $user->ID ) ) {
                return $user;
            } else {
                return new WP_Error( 'authentication_failed', 'رمز عبور اشتباه است.' );
            }
        }
    }
    
    return $user;
}
add_filter( 'authenticate', 'nardone_mobile_login_auth', 20, 3 );

/**
 * Change login form labels
 */
function nardone_change_login_labels( $translated_text, $text, $domain ) {
    if ( $domain === 'woocommerce' ) {
        switch ( $text ) {
            case 'Username or email address':
            case 'Username or email':
                return 'شماره موبایل یا نام کاربری';
            case 'Password':
                return 'رمز عبور';
            case 'Remember me':
                return 'مرا به خاطر بسپار';
            case 'Log in':
                return 'ورود';
            case 'Lost your password?':
                return 'رمز عبور خود را فراموش کرده‌اید؟';
        }
    }
    return $translated_text;
}
add_filter( 'gettext', 'nardone_change_login_labels', 20, 3 );
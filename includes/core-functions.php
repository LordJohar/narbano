<?php
/**
 * Core functions for Nardone Registration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize Persian/Arabic digits to Latin digits
 */
function nardone_normalize_phone_digits( $raw ) {
    if ( empty( $raw ) ) {
        return '';
    }
    
    $str = wp_unslash( $raw );
    
    // Persian digits
    $persian_digits = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
    // Arabic digits
    $arabic_digits  = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );
    // Latin digits
    $latin_digits   = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
    
    // Convert
    $str = str_replace( $persian_digits, $latin_digits, $str );
    $str = str_replace( $arabic_digits,  $latin_digits, $str );
    
    // Remove spaces
    $str = preg_replace( '/\s+/', '', $str );
    
    return $str;
}

/**
 * Generate unique username
 */
function nardone_generate_username() {
    do {
        $rand     = wp_rand( 1000, 9999 );
        $username = 'nardone' . $rand;
    } while ( username_exists( $username ) );
    
    return $username;
}

/**
 * Check if string is valid Iranian mobile number
 */
function nardone_is_valid_mobile( $phone ) {
    $phone = nardone_normalize_phone_digits( $phone );
    return preg_match( '/^09[0-9]{9}$/', $phone );
}

/**
 * Check if mobile number already exists
 */
function nardone_mobile_exists( $phone ) {
    $phone = nardone_normalize_phone_digits( $phone );
    
    $users = get_users( array(
        'meta_key'   => 'billing_phone',
        'meta_value' => $phone,
        'number'     => 1,
        'fields'     => 'ID',
    ) );
    
    return ! empty( $users );
}
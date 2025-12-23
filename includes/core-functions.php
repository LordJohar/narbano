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

/**
 * Find a user by billing phone (normalized).
 *
 * @param string $phone Raw or normalized phone.
 * @return WP_User|null
 */
function nardone_find_user_by_billing_phone( $phone ) {
    $phone = nardone_normalize_phone_digits( $phone );

    if ( empty( $phone ) ) {
        return null;
    }

    $users = get_users( array(
        'meta_key'   => 'billing_phone',
        'meta_value' => $phone,
        'number'     => 1,
        'fields'     => 'all',
    ) );

    if ( empty( $users ) || ! isset( $users[0] ) ) {
        return null;
    }

    return $users[0];
}

/**
 * Mask a user's name as "F.Family" (first char of first name + "." + last name).
 * Falls back to display_name when names are missing.
 *
 * @param WP_User $user
 * @return string
 */
function nardone_mask_user_name( $user ) {
    if ( ! $user instanceof WP_User ) {
        return '';
    }

    $first = get_user_meta( $user->ID, 'first_name', true );
    $last  = get_user_meta( $user->ID, 'last_name', true );

    // Fallback to display_name if names are empty.
    if ( empty( $first ) && empty( $last ) ) {
        return $user->display_name ?: '';
    }

    $first_char = '';

    if ( ! empty( $first ) ) {
        if ( function_exists( 'mb_substr' ) ) {
            $first_char = mb_substr( $first, 0, 1 );
        } else {
            $first_char = substr( $first, 0, 1 );
        }
    }

    $parts = array();

    if ( ! empty( $first_char ) ) {
        $parts[] = $first_char . '.';
    }

    if ( ! empty( $last ) ) {
        $parts[] = $last;
    }

    return trim( implode( '', $parts ) );
}
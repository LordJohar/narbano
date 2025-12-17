<?php
/**
 * Helper functions (normalizers, utilities).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

/**
 * Convert Persian/Arabic digits to Latin digits and strip whitespace.
 *
 * @param string $raw Raw phone/number string.
 * @return string Normalized numeric string.
 */
function nardone_normalize_phone_digits( $raw ) {
    $str = wp_unslash( $raw );

    $persian_digits = array( '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹' );
    $arabic_digits  = array( '٠','١','٢','٣','٤','٥','٦','٧','٨','٩' );
    $latin_digits   = array( '0','1','2','3','4','5','6','7','8','9' );

    $str = str_replace( $persian_digits, $latin_digits, $str );
    $str = str_replace( $arabic_digits,  $latin_digits, $str );

    // Remove whitespace.
    $str = preg_replace( '/\s+/', '', $str );

    return $str;
}

/**
 * Generate a unique username in the format nardoneXXXX.
 *
 * @return string
 */
function nardone_generate_username() {
    do {
        $rand     = wp_rand( 1000, 9999 );
        $username = 'nardone' . $rand;
    } while ( username_exists( $username ) );

    return $username;
}

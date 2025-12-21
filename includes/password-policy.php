<?php
/**
 * Password policy adjustments.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

/**
 * Allow any password strength (only display meter).
 * DISABLED: Strong password policy is not enforced during registration.
 *
 * @param int $min_strength Minimum strength required by WooCommerce.
 * @return int
 */
function nardone_allow_any_password_strength( $min_strength ) {
    return 0;
}
// add_filter( 'woocommerce_min_password_strength', 'nardone_allow_any_password_strength', 10, 1 );

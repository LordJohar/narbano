<?php
/**
 * Dependency checks (WooCommerce presence).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

/**
 * Ensure WooCommerce is active; otherwise show admin notice.
 */
function nardone_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo esc_html__( 'Nardone Registration requires WooCommerce to be active.', 'nardone' );
            echo '</p></div>';
        } );
    }
}
add_action( 'plugins_loaded', 'nardone_check_woocommerce' );

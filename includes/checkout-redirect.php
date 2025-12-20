<?php
/**
 * Checkout redirect and customization for Nardone Registration
 */
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Redirect non-logged in users to login page when trying to access checkout
 */
function nardone_redirect_checkout_to_login() {
    // Only on checkout page
    if (!is_checkout() || is_user_logged_in()) {
        return;
    }
    
    // Don't redirect during AJAX requests
    if (wp_doing_ajax()) {
        return;
    }
    
    // Don't redirect if it's the order-received page
    if (is_wc_endpoint_url('order-received')) {
        return;
    }
    
    // Store checkout URL in session to redirect back after login
    if (!isset(WC()->session)) {
        return;
    }
    
    WC()->session->set('nardone_checkout_redirect', wc_get_checkout_url());
    
    // Redirect to login page
    wp_redirect(wc_get_page_permalink('myaccount'));
    exit;
}
add_action('template_redirect', 'nardone_redirect_checkout_to_login', 10);
/**
 * Redirect users back to checkout after login
 */
function nardone_redirect_after_login($redirect, $user) {
    if (isset(WC()->session)) {
        $checkout_url = WC()->session->get('nardone_checkout_redirect');
        
        if ($checkout_url) {
            WC()->session->__unset('nardone_checkout_redirect');
            return $checkout_url;
        }
    }
    
    return $redirect;
}
add_filter('woocommerce_login_redirect', 'nardone_redirect_after_login', 10, 2);
/**
 * Add login notice on checkout page for non-logged in users
 */
function nardone_checkout_login_notice() {
    if (is_checkout() && !is_user_logged_in()) {
        // This notice will show briefly before redirect
        wc_add_notice(
            'برای تکمیل خرید ابتدا باید وارد حساب کاربری خود شوید. در حال انتقال به صفحه ورود...',
            'notice'
        );
    }
}
add_action('wp', 'nardone_checkout_login_notice');
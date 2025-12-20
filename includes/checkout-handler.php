<?php
/**
 * Checkout modifications for Nardone Registration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simplify checkout fields for logged-in users
 */
function nardone_simplify_checkout_fields($fields) {
    // Only simplify for logged-in users
    if (!is_user_logged_in()) {
        return $fields;
    }
    
    $current_user = wp_get_current_user();
    
    // Hide unnecessary fields for logged-in users
    $fields_to_hide = array(
        'billing' => array(
            'billing_first_name',
            'billing_last_name',
            'billing_email',
            'billing_phone',
            'billing_company',
        ),
        'shipping' => array(
            'shipping_first_name',
            'shipping_last_name',
            'shipping_company',
        ),
        'account' => array(
            'account_username',
            'account_password',
            'account_password-2',
        )
    );
    
    foreach ($fields_to_hide as $section => $field_names) {
        if (isset($fields[$section])) {
            foreach ($field_names as $field_name) {
                if (isset($fields[$section][$field_name])) {
                    $fields[$section][$field_name]['class'][] = 'nardone-hidden-field';
                }
            }
        }
    }
    
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'nardone_simplify_checkout_fields', 9999);

/**
 * Auto-fill checkout fields for logged-in users
 */
function nardone_autofill_checkout_fields() {
    if (!is_user_logged_in() || !is_checkout()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Get user meta
    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name = get_user_meta($user_id, 'last_name', true);
    $phone = get_user_meta($user_id, 'billing_phone', true);
    $email = $current_user->user_email;
    
    // Auto-fill fields via JavaScript
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Only run if user is logged in
        if (typeof nardone_user_data !== 'undefined') {
            return;
        }
        
        // Set user data
        window.nardone_user_data = {
            first_name: '<?php echo esc_js($first_name); ?>',
            last_name: '<?php echo esc_js($last_name); ?>',
            phone: '<?php echo esc_js($phone); ?>',
            email: '<?php echo esc_js($email); ?>'
        };
        
        // Auto-fill fields
        if (nardone_user_data.first_name) {
            $('#billing_first_name').val(nardone_user_data.first_name);
        }
        
        if (nardone_user_data.last_name) {
            $('#billing_last_name').val(nardone_user_data.last_name);
        }
        
        if (nardone_user_data.phone) {
            $('#billing_phone').val(nardone_user_data.phone);
        }
        
        if (nardone_user_data.email) {
            $('#billing_email').val(nardone_user_data.email);
        }
        
        // Copy billing to shipping if checkbox is checked
        $('#ship-to-different-address-checkbox').on('change', function() {
            if (!$(this).is(':checked')) {
                $('#shipping_first_name').val(nardone_user_data.first_name);
                $('#shipping_last_name').val(nardone_user_data.last_name);
            }
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'nardone_autofill_checkout_fields');

/**
 * Create a simplified checkout form for logged-in users
 */
function nardone_simplified_checkout_form() {
    if (!is_checkout() || !is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    ?>
    
    <div class="nardone-user-info-checkout">
        <div class="user-info-summary">
            <h3>اطلاعات شما</h3>
            <div class="info-row">
                <span class="label">نام:</span>
                <span class="value"><?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?></span>
            </div>
            <div class="info-row">
                <span class="label">موبایل:</span>
                <span class="value"><?php echo esc_html(get_user_meta($current_user->ID, 'billing_phone', true)); ?></span>
            </div>
            <div class="info-row">
                <span class="label">ایمیل:</span>
                <span class="value"><?php echo esc_html($current_user->user_email); ?></span>
            </div>
            <a href="<?php echo wc_get_account_endpoint_url('edit-account'); ?>" class="edit-info-link">
                ویرایش اطلاعات
            </a>
        </div>
    </div>
    
    <?php
}
add_action('woocommerce_before_checkout_form', 'nardone_simplified_checkout_form', 5);

/**
 * Add custom CSS for simplified checkout
 */
function nardone_checkout_custom_styles() {
    if (!is_checkout()) {
        return;
    }
    
    ?>
    <style>
    /* Hide auto-filled fields for logged-in users */
    .nardone-hidden-field {
        display: none !important;
    }
    
    /* User info summary box */
    .nardone-user-info-checkout {
        background: #f9f9f9;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        max-width: 500px;
    }
    
    .nardone-user-info-checkout h3 {
        margin-top: 0;
        color: #333;
        border-bottom: 2px solid #96588a;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    
    .nardone-user-info-checkout .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding: 8px 0;
        border-bottom: 1px dashed #ddd;
    }
    
    .nardone-user-info-checkout .info-row:last-child {
        border-bottom: none;
    }
    
    .nardone-user-info-checkout .label {
        font-weight: bold;
        color: #555;
    }
    
    .nardone-user-info-checkout .value {
        color: #333;
    }
    
    .nardone-user-info-checkout .edit-info-link {
        display: inline-block;
        margin-top: 15px;
        color: #96588a;
        text-decoration: none;
        font-size: 14px;
    }
    
    .nardone-user-info-checkout .edit-info-link:hover {
        text-decoration: underline;
    }
    
    /* Highlight address section */
    #customer_details .col-1 {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .nardone-user-info-checkout {
            margin: 0 15px 20px 15px;
        }
    }
    
    /* Login prompt for non-logged in users */
    .checkout-login-prompt {
        text-align: center;
        padding: 40px 20px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        margin: 30px auto;
        max-width: 500px;
    }
    
    .checkout-login-prompt h2 {
        color: #96588a;
        margin-bottom: 20px;
    }
    
    .checkout-login-prompt .login-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 25px;
    }
    
    .checkout-login-prompt .login-button {
        padding: 12px 25px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s;
    }
    
    .checkout-login-prompt .login-button-primary {
        background: #96588a;
        color: white;
        border: 2px solid #96588a;
    }
    
    .checkout-login-prompt .login-button-primary:hover {
        background: #7a4970;
        border-color: #7a4970;
    }
    
    .checkout-login-prompt .login-button-secondary {
        background: white;
        color: #96588a;
        border: 2px solid #96588a;
    }
    
    .checkout-login-prompt .login-button-secondary:hover {
        background: #f9f9f9;
    }
    </style>
    <?php
}
add_action('wp_head', 'nardone_checkout_custom_styles');
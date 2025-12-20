<?php
/**
 * Custom checkout login/registration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display custom login/registration on checkout for non-logged in users
 */
function nardone_checkout_login_screen() {
    if (!is_checkout() || is_user_logged_in()) {
        return;
    }
    
    // Don't show on order-received page
    if (is_wc_endpoint_url('order-received')) {
        return;
    }
    
    // Hide default checkout form
    add_filter('woocommerce_checkout_coupon_message', '__return_empty_string');
    add_filter('woocommerce_checkout_login_message', '__return_empty_string');
    
    // Display custom login/registration
    ?>
    <div class="checkout-login-prompt">
        <h2>🔒 تکمیل خرید</h2>
        <p>برای ادامه فرآیند خرید، لطفاً وارد حساب کاربری خود شوید.</p>
        <p>اگر حساب کاربری ندارید، می‌توانید در کمتر از ۱ دقیقه ثبت‌نام کنید.</p>
        
        <div class="login-buttons">
            <a href="<?php echo wc_get_page_permalink('myaccount'); ?>" class="login-button login-button-primary">
                ورود به حساب کاربری
            </a>
            <a href="<?php echo wc_get_page_permalink('myaccount'); ?>#register" class="login-button login-button-secondary">
                ثبت‌نام سریع
            </a>
        </div>
        
        <div class="guest-checkout-option" style="margin-top: 30px; padding-top: 20px; border-top: 1px dashed #ddd;">
            <p style="font-size: 14px; color: #666;">
                <a href="javascript:void(0)" id="enable-guest-checkout" style="color: #96588a;">
                    می‌خواهید بدون ثبت‌نام خرید کنید؟
                </a>
            </p>
            
            <div id="guest-checkout-form" style="display: none; margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px;">
                <p style="margin-bottom: 15px; font-size: 14px;">
                    برای پیگیری سفارش، لطفاً شماره موبایل خود را وارد کنید:
                </p>
                
                <div class="form-row" style="margin-bottom: 15px;">
                    <label for="guest_phone" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        شماره موبایل <span style="color: red;">*</span>
                    </label>
                    <input type="tel" id="guest_phone" name="guest_phone" 
                           placeholder="09123456789" 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 3px;">
                </div>
                
                <div class="form-row" style="margin-bottom: 15px;">
                    <label for="guest_email" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        ایمیل (اختیاری)
                    </label>
                    <input type="email" id="guest_email" name="guest_email" 
                           placeholder="email@example.com" 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 3px;">
                </div>
                
                <button type="button" id="continue-as-guest" style="background: #96588a; color: white; border: none; padding: 10px 20px; border-radius: 3px; cursor: pointer;">
                    ادامه به عنوان مهمان
                </button>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Hide default checkout form
        $('form.woocommerce-checkout').hide();
        $('.woocommerce-info, .woocommerce-message').hide();
        
        // Toggle guest checkout form
        $('#enable-guest-checkout').on('click', function() {
            $('#guest-checkout-form').slideToggle();
        });
        
        // Continue as guest
        $('#continue-as-guest').on('click', function() {
            var phone = $('#guest_phone').val().trim();
            var email = $('#guest_email').val().trim();
            
            // Normalize phone digits
            var persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            var arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
            for (var i = 0; i < 10; i++) {
                var p = new RegExp(persian[i], 'g');
                var a = new RegExp(arabic[i], 'g');
                phone = phone.replace(p, i).replace(a, i);
            }
            phone = phone.replace(/\s+/g, '');
            
            // Validate phone
            if (!phone) {
                alert('لطفا شماره موبایل را وارد کنید.');
                return;
            }
            
            var phoneRegex = /^09[0-9]{9}$/;
            if (!phoneRegex.test(phone)) {
                alert('شماره موبایل معتبر نیست. مثال: 09123456789');
                return;
            }
            
            // Generate fake email if empty
            if (!email) {
                email = 'guest_' + phone + '_' + Date.now() + '@noemail.nardone';
                $('#guest_email').val(email);
            }
            
            // Fill checkout form with guest data
            $('#billing_phone').val(phone);
            $('#billing_email').val(email);
            $('#billing_first_name').val('مهمان');
            $('#billing_last_name').val('(خرید آنلاین)');
            
            // Show checkout form
            $('form.woocommerce-checkout').show();
            $('.checkout-login-prompt').hide();
            
            // Scroll to checkout form
            $('html, body').animate({
                scrollTop: $('form.woocommerce-checkout').offset().top - 100
            }, 500);
        });
    });
    </script>
    <?php
}
add_action('woocommerce_before_checkout_form', 'nardone_checkout_login_screen', 1);
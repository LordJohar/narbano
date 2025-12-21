<?php
/**
 * Frontend assets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue scripts and styles (OTP handling on My Account register form)
 */
function nardone_frontend_scripts() {
    // فقط در صفحه حساب کاربری
    if ( ! is_account_page() ) {
        return;
    }

    // مطمئن شو jQuery لود شده
    wp_enqueue_script( 'jquery' );

    // یک اسکریپت خالی رجیستر می‌کنیم تا اینلاین اسکریپت را به آن بچسبانیم
    wp_register_script(
        'nardone-frontend',
        '',
        array( 'jquery' ),
        null,
        true
    );
    wp_enqueue_script( 'nardone-frontend' );

    // داده‌های AJAX (آدرس admin-ajax و nonce)
    wp_localize_script(
        'nardone-frontend',
        'NardoneData',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'nardone_send_otp' ),
        )
    );

    // جاوااسکریپت فرانت‌اند
    $script = "
    jQuery(function($) {
        // تبدیل ارقام فارسی/عربی به لاتین و حذف فاصله‌ها
        function normalizeDigits(str) {
            if (!str) return '';
            var persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            var arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
            for (var i = 0; i < 10; i++) {
                var p = new RegExp(persian[i], 'g');
                var a = new RegExp(arabic[i], 'g');
                str = str.replace(p, i).replace(a, i);
            }
            return str.replace(/\\s+/g, '');
        }
        
        // نرمال‌سازی ورودی‌ها هنگام blur
        $('#reg_billing_phone, #reg_nardone_otp_code, #username').on('blur', function() {
            $(this).val(normalizeDigits($(this).val()));
        });
        
        // جابجاکردن فیلد پسورد زیر فیلد OTP
        var \$passwordRow = $('form.register #reg_password').closest('p');
        var \$otpRow      = $('form.register #reg_nardone_otp_code').closest('p');
        if (\$passwordRow.length && \$otpRow.length) {
            \$passwordRow.insertAfter(\$otpRow);
        }
        
        // کلیک روی دکمه ارسال کد
        $('#nardone_send_otp_btn').on('click', function(e) {
            e.preventDefault();
            var \$btn  = $(this);
            var phone  = normalizeDigits($('#reg_billing_phone').val());
            $('#reg_billing_phone').val(phone);
            
            if (!phone) {
                alert('لطفا شماره موبایل را وارد کنید.');
                return;
            }
            
            if (!/^09[0-9]{9}$/.test(phone)) {
                alert('شماره موبایل معتبر نیست. مثال: 09121234567');
                return;
            }
            
            if (\$btn.data('sending')) {
                return;
            }
            \$btn.data('sending', true).text('در حال ارسال...').prop('disabled', true);
            
            $.ajax({
                url: NardoneData.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'nardone_send_otp',
                    nonce:  NardoneData.nonce,
                    phone:  phone
                }
            })
            .done(function(response) {
                var msg;
                if (response && response.success) {
                    msg = (response.data && response.data.message)
                        ? response.data.message
                        : 'کد تأیید ارسال شد.';
                } else {
                    msg = (response && response.data && response.data.message)
                        ? response.data.message
                        : 'خطا در ارسال کد.';
                }
                alert(msg);
            })
            .fail(function() {
                alert('خطای ارتباط.');
            })
            .always(function() {
                \$btn.data('sending', false)
                    .text('ارسال کد تأیید')
                    .prop('disabled', false);
            });
        });
    });
    ";

    wp_add_inline_script( 'nardone-frontend', $script );
}
add_action( 'wp_enqueue_scripts', 'nardone_frontend_scripts' );
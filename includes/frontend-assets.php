<?php
/**
 * Frontend assets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue scripts and styles
 */
function nardone_frontend_scripts() {
    if ( ! is_account_page() ) {
        return;
    }

    wp_register_script(
        'nardone-frontend',
        '',
        array( 'jquery' ),
        null,
        true
    );
    wp_enqueue_script( 'nardone-frontend' );

    wp_localize_script(
        'nardone-frontend',
        'NardoneData',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'nardone_send_otp' ),
        )
    );

    $script = "
    jQuery(function($) {
        // Normalize digits function
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
        
        // Normalize inputs
        $('#reg_billing_phone, #reg_nardone_otp_code, #username').on('blur', function() {
            $(this).val(normalizeDigits($(this).val()));
        });
        
        // Move password field
        var \$passwordRow = $('form.register #reg_password').closest('p');
        var \$otpRow = $('form.register #reg_nardone_otp_code').closest('p');
        if (\$passwordRow.length && \$otpRow.length) {
            \$passwordRow.insertAfter(\$otpRow);
        }
        
        // OTP send button
        $('#nardone_send_otp_btn').on('click', function(e) {
            e.preventDefault();
            var \$btn = $(this);
            var phone = normalizeDigits($('#reg_billing_phone').val());
            $('#reg_billing_phone').val(phone);
            
            if (!phone) {
                alert('لطفا شماره موبایل را وارد کنید.');
                return;
            }
            
            if (!/^09[0-9]{9}$/.test(phone)) {
                alert('شماره موبایل معتبر نیست.');
                return;
            }
            
            if (\$btn.data('sending')) return;
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
            }), function(response) {
                if (response.success) {
                    alert('کد تأیید ارسال شد.');
                } else {
                    alert(response.data?.message || 'خطا در ارسال کد.');
                }
            }).fail(function() {
                alert('خطای ارتباط.');
            }).always(function() {
                \$btn.data('sending', false).text('ارسال کد تأیید').prop('disabled', false);
            });
        });
    });
    ";
};
add_action( 'wp_enqueue_scripts', 'nardone_frontend_assets' );
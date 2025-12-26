<?php
/**
 * Front-end assets and inline behaviors for OTP.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

/**
 * Enqueue front-end script for OTP handling and form tweaks.
 */
function nardone_frontend_scripts() {
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

        // Convert Persian/Arabic digits to Latin digits.
        function nardoneNormalizeDigits(str) {
            if (!str) return '';
            var persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            var arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
            for (var i = 0; i < 10; i++) {
                var p = new RegExp(persian[i], 'g');
                var a = new RegExp(arabic[i],  'g');
                str = str.replace(p, i).replace(a, i);
            }
            return str;
        }

        // Normalize OTP on blur.
        $('#reg_nardone_otp_code').on('blur', function() {
            var val = $(this).val();
            val = nardoneNormalizeDigits(val);
            $(this).val(val);
        });
        // Referrer lookup
        var $refInput  = $('#reg_nardone_referrer_phone');
        var $refStatus = $('.nardone-referrer-status');

        function setRefStatus(message, cls) {
            $refStatus.removeClass('is-success is-error');
            if (cls) {
                $refStatus.addClass(cls);
            }
            $refStatus.text(message || '');
        }

        function normalizePhone(raw) {
            raw = nardoneNormalizeDigits(raw || '');
            // remove non-digits
            raw = raw.replace(/\D+/g, '');
            // handle 0098 / 98 / +98 prefixes
            if (raw.indexOf('0098') === 0 && raw.length >= 14) {
                raw = '0' + raw.substring(4);
            } else if (raw.indexOf('98') === 0 && raw.length >= 12) {
                raw = '0' + raw.substring(2);
            } else if (raw.indexOf('9') === 0 && raw.length === 10) {
                raw = '0' + raw;
            }
            return raw;
        }

        function lookupReferrer(phone) {
            setRefStatus('" . esc_js( __( 'در حال بررسی...', 'nardone' ) ) . "', '');

            $.ajax({
                url: NardoneData.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'nardone_check_referrer',
                    nonce:  NardoneData.nonce,
                    phone:  phone
                }
            })
            .done(function(response) {
                if (response && response.success && response.data && response.data.name_mask) {
                    setRefStatus(response.data.name_mask, 'is-success');
                } else {
                    var msg = (response && response.data && response.data.message)
                        ? response.data.message
                        : '" . esc_js( __( 'معرفی با این شماره یافت نشد.', 'nardone' ) ) . "';
                    setRefStatus(msg, 'is-error');
                }
            })
            .fail(function() {
                setRefStatus('" . esc_js( __( 'خطا در برقراری ارتباط. دوباره تلاش کنید.', 'nardone' ) ) . "', 'is-error');
            });
        }

        $refInput.on('blur', function() {
            var raw   = $(this).val();
            var phone = normalizePhone(raw);
            $(this).val(phone);

            if (!phone) {
                setRefStatus('', '');
                return;
            }

            var phoneRegex = /^09[0-9]{9}$/;
            if (!phoneRegex.test(phone)) {
                setRefStatus('" . esc_js( __( 'شماره معتبر نیست (مثال: 09121234567)', 'nardone' ) ) . "', 'is-error');
                return;
            }

            lookupReferrer(phone);
        });
        var \$form = $('form.register');
        if (!\$form.length) {
            return;
        }

        // Move password field under OTP field for better UX.
        var \$passwordRow = \$form.find('#reg_password').closest('p');
        var \$otpRow      = \$form.find('#reg_nardone_otp_code').closest('p');

        if (\$passwordRow.length && \$otpRow.length) {
            \$passwordRow.insertAfter(\$otpRow);
        }

        // Handle OTP send button.
        $('#nardone_send_otp_btn').on('click', function(e) {
            e.preventDefault();

            var \$btn     = $(this);
            var phoneRaw  = $.trim( $('#reg_billing_phone').val() );
            var phone     = nardoneNormalizeDigits(phoneRaw);
            $('#reg_billing_phone').val(phone);

            if (!phone) {
                alert('Please enter your mobile number first.');
                return;
            }

            var phoneRegex = /^09[0-9]{9}$/;
            if (!phoneRegex.test(phone)) {
                alert('Invalid mobile number. Example: 09121234567');
                return;
            }

            if (\$btn.data('sending')) {
                return;
            }
            \$btn.data('sending', true);
            var oldText = \$btn.text();
            \$btn.text('Sending...').prop('disabled', true);

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
                if (response && response.success) {
                    alert(response.data && response.data.message ? response.data.message : 'Verification code sent.');
                } else {
                    var msg = (response && response.data && response.data.message)
                        ? response.data.message
                        : 'Failed to send verification code. Please try again.';
                    alert(msg);
                }
            })
            .fail(function() {
                alert('Server communication error. Please try again later.');
            })
            .always(function() {
                \$btn.data('sending', false);
                \$btn.text(oldText).prop('disabled', false);
            });
        });

        // Referrer toggle + lookup
        var $refToggle = $('.nardone-ref-toggle');
        var $refField  = $('.nardone-referrer-field');
        var $refInput  = $('#reg_nardone_referrer_phone');
        var $refStatus = $('.nardone-referrer-status');

        function setRefStatus(message, cls) {
            \$refStatus.removeClass('is-success is-error');
            if (cls) {
                \$refStatus.addClass(cls);
            }
            \$refStatus.text(message || '');
        }

        function normalizePhone(raw) {
            raw = nardoneNormalizeDigits(raw || '');
            // remove non-digits
            raw = raw.replace(/\D+/g, '');
            // handle 0098 / 98 / +98 prefixes
            if (raw.indexOf('0098') === 0 && raw.length >= 14) {
                raw = '0' + raw.substring(4);
            } else if (raw.indexOf('98') === 0 && raw.length >= 12) {
                raw = '0' + raw.substring(2);
            } else if (raw.indexOf('9') === 0 && raw.length === 10) {
                raw = '0' + raw;
            }
            return raw;
        }

        \$refToggle.on('click', function(e) {
            e.preventDefault();
            var expanded = \$refField.hasClass('is-visible');
            if (expanded) {
                \$refField.removeClass('is-visible').slideUp(200);
                \$refToggle.attr('aria-expanded', 'false');
                \$refInput.val('');
                setRefStatus('', '');
            } else {
                \$refField.addClass('is-visible').slideDown(200);
                \$refToggle.attr('aria-expanded', 'true');
                \$refInput.trigger('focus');
            }
        });

        function lookupReferrer(phone) {
            setRefStatus('" . esc_js( __( 'در حال بررسی...', 'nardone' ) ) . "', '');

            $.ajax({
                url: NardoneData.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'nardone_check_referrer',
                    nonce:  NardoneData.nonce,
                    phone:  phone
                }
            })
            .done(function(response) {
                if (response && response.success && response.data && response.data.name_mask) {
                    setRefStatus(response.data.name_mask, 'is-success');
                } else {
                    var msg = (response && response.data && response.data.message)
                        ? response.data.message
                        : '" . esc_js( __( 'معرفی با این شماره یافت نشد.', 'nardone' ) ) . "';
                    setRefStatus(msg, 'is-error');
                }
            })
            .fail(function() {
                setRefStatus('" . esc_js( __( 'خطا در برقراری ارتباط. دوباره تلاش کنید.', 'nardone' ) ) . "', 'is-error');
            });
        }

        \$refInput.on('blur', function() {
            var raw   = $(this).val();
            var phone = normalizePhone(raw);
            $(this).val(phone);

            if (!phone) {
                setRefStatus('', '');
                return;
            }

            var phoneRegex = /^09[0-9]{9}$/;
            if (!phoneRegex.test(phone)) {
                setRefStatus('" . esc_js( __( 'شماره معتبر نیست (مثال: 09121234567)', 'nardone' ) ) . "', 'is-error');
                return;
            }

            lookupReferrer(phone);
        });

    });
    ";

    wp_add_inline_script( 'nardone-frontend', $script );
}
add_action( 'wp_enqueue_scripts', 'nardone_frontend_scripts' );

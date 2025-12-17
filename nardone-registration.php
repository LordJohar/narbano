<?php
/**
 * Plugin Name: Nardone Registration
 * Description: ثبت‌نام کاربران با موبایل، OTP و یوزرنیم برای ووکامرس.
 * Author: Lord Johar
 * Version: 0.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // جلوگیری از دسترسی مستقیم
}

// تنظیمات ثابت OTP
define( 'NARDONE_OTP_API_URL', 'https://edge.ippanel.com/v1/api/send' );
define( 'NARDONE_OTP_EXPIRY',  3 * MINUTE_IN_SECONDS ); // اعتبار کد: 3 دقیقه

/**
 * اطمینان از فعال بودن ووکامرس
 */
function nardone_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo 'افزونه Nardone Registration نیاز به ووکامرس فعال دارد.';
            echo '</p></div>';
        } );
    }
}
add_action( 'plugins_loaded', 'nardone_check_woocommerce' );

/**
 * تبدیل اعداد فارسی/عربی به لاتین و حذف فاصله‌ها در رشته عددی (موبایل، OTP و...)
 */
function nardone_normalize_phone_digits( $raw ) {
    $str = wp_unslash( $raw );

    // اعداد فارسی
    $persian_digits = array( '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹' );
    // اعداد عربی
    $arabic_digits  = array( '٠','١','٢','٣','٤','٥','٦','٧','٨','٩' );
    // اعداد لاتین
    $latin_digits   = array( '0','1','2','3','4','5','6','7','8','9' );

    // تبدیل
    $str = str_replace( $persian_digits, $latin_digits, $str );
    $str = str_replace( $arabic_digits,  $latin_digits, $str );

    // حذف فاصله‌ها
    $str = preg_replace( '/\s+/', '', $str );

    return $str;
}

/**
 * صفحه تنظیمات OTP افزونه Nardone
 */
function nardone_add_settings_page() {
    add_options_page(
        'تنظیمات Nardone OTP',
        'Nardone OTP',
        'manage_options',
        'nardone-otp-settings',
        'nardone_render_settings_page'
    );
}
add_action( 'admin_menu', 'nardone_add_settings_page' );

/**
 * رندر صفحه تنظیمات
 */
function nardone_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // ذخیره تنظیمات
    if ( isset( $_POST['nardone_otp_save_settings'] ) ) {
        check_admin_referer( 'nardone_otp_save_settings_nonce' );

        $api_key       = isset( $_POST['nardone_otp_api_key'] ) ? trim( wp_unslash( $_POST['nardone_otp_api_key'] ) ) : '';
        $pattern_code  = isset( $_POST['nardone_otp_pattern_code'] ) ? trim( wp_unslash( $_POST['nardone_otp_pattern_code'] ) ) : '';
        $from_number   = isset( $_POST['nardone_otp_from_number'] ) ? trim( wp_unslash( $_POST['nardone_otp_from_number'] ) ) : '';
        $pattern_param = isset( $_POST['nardone_otp_pattern_param'] ) ? trim( wp_unslash( $_POST['nardone_otp_pattern_param'] ) ) : '';

        update_option( 'nardone_otp_api_key',       sanitize_text_field( $api_key ) );
        update_option( 'nardone_otp_pattern_code',  sanitize_text_field( $pattern_code ) );
        update_option( 'nardone_otp_from_number',   sanitize_text_field( $from_number ) );
        update_option( 'nardone_otp_pattern_param', sanitize_key( $pattern_param ?: 'otp_code' ) );

        echo '<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
    }

    // خواندن مقادیر فعلی
    $api_key       = get_option( 'nardone_otp_api_key', '' );
    $pattern_code  = get_option( 'nardone_otp_pattern_code', '' );
    $from_number   = get_option( 'nardone_otp_from_number', '' );
    $pattern_param = get_option( 'nardone_otp_pattern_param', 'otp_code' );

    ?>
    <div class="wrap">
        <h1>تنظیمات Nardone OTP</h1>
        <form method="post">
            <?php wp_nonce_field( 'nardone_otp_save_settings_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="nardone_otp_api_key">API Key</label></th>
                        <td>
                            <input type="text" id="nardone_otp_api_key" name="nardone_otp_api_key" class="regular-text" value="<?php echo esc_attr( $api_key ); ?>" />
                            <p class="description">کلید API که IPPanel به شما داده است (هدر Authorization).</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="nardone_otp_pattern_code">Pattern Code</label></th>
                        <td>
                            <input type="text" id="nardone_otp_pattern_code" name="nardone_otp_pattern_code" class="regular-text" value="<?php echo esc_attr( $pattern_code ); ?>" />
                            <p class="description">کد پترنی که در IPPanel ساخته‌اید (مثال: zvtfo5o4badaang).</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="nardone_otp_from_number">شماره فرستنده</label></th>
                        <td>
                            <input type="text" id="nardone_otp_from_number" name="nardone_otp_from_number" class="regular-text" value="<?php echo esc_attr( $from_number ); ?>" />
                            <p class="description">مثال: +983000505</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="nardone_otp_pattern_param">نام متغیر پترن</label></th>
                        <td>
                            <input type="text" id="nardone_otp_pattern_param" name="nardone_otp_pattern_param" class="regular-text" value="<?php echo esc_attr( $pattern_param ); ?>" />
                            <p class="description">نام متغیری که در متن پترن استفاده کرده‌اید (مثلا otp_code). مقدار کد در این فیلد ارسال می‌شود.</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <button type="submit" name="nardone_otp_save_settings" class="button button-primary">ذخیره تغییرات</button>
            </p>
        </form>
    </div>
    <?php
}

/**
 * افزودن فیلدهای سفارشی به فرم ثبت‌نام ووکامرس
 */
function nardone_add_registration_fields() {
    ?>
    <p class="form-row form-row-first">
        <label for="reg_billing_first_name">نام&nbsp;<span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php echo isset( $_POST['billing_first_name'] ) ? esc_attr( wp_unslash( $_POST['billing_first_name'] ) ) : ''; ?>" />
    </p>

    <p class="form-row form-row-last">
        <label for="reg_billing_last_name">نام خانوادگی&nbsp;<span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php echo isset( $_POST['billing_last_name'] ) ? esc_attr( wp_unslash( $_POST['billing_last_name'] ) ) : ''; ?>" />
    </p>

    <p class="form-row form-row-wide">
        <label for="reg_billing_phone">شماره موبایل&nbsp;<span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_phone" id="reg_billing_phone" value="<?php echo isset( $_POST['billing_phone'] ) ? esc_attr( wp_unslash( $_POST['billing_phone'] ) ) : ''; ?>" placeholder="مثال: 09121234567" />
    </p>

    <p class="form-row form-row-wide">
        <label for="reg_nardone_otp_code">کد تأیید (OTP)&nbsp;<span class="required">*</span></label>
        <input type="text" class="input-text" name="nardone_otp_code" id="reg_nardone_otp_code" value="" />
        <button type="button" class="button" id="nardone_send_otp_btn" style="margin-top:8px;">
            ارسال کد تأیید به موبایل
        </button>
        <small class="description">بعد از دریافت کد، آن را در فیلد بالا وارد کنید.</small>
    </p>

    <div style="clear:both"></div>
    <?php
}
add_action( 'woocommerce_register_form', 'nardone_add_registration_fields' );

/**
 * تولید یوزرنیم یکتا با فرمت nardoneXXXX
 */
function nardone_generate_username() {
    do {
        $rand     = wp_rand( 1000, 9999 );
        $username = 'nardone' . $rand;
    } while ( username_exists( $username ) );

    return $username;
}

/**
 * تنظیم یوزرنیم سفارشی و ایمیل مصنوعی (در صورت نبود ایمیل)
 */
function nardone_customize_new_customer_data( $data ) {

    // یوزرنیم سفارشی
    $data['user_login'] = nardone_generate_username();

    // اگر ایمیلی ست نشده، یک ایمیل فیک براساس موبایل بساز
    if ( empty( $data['user_email'] ) && ! empty( $_POST['billing_phone'] ) ) {
        $phone_digits = preg_replace( '/\D/', '', nardone_normalize_phone_digits( $_POST['billing_phone'] ) );
        if ( empty( $phone_digits ) ) {
            $phone_digits = wp_rand( 10000000, 99999999 );
        }

        $fake_email         = 'u' . $phone_digits . '-' . wp_rand( 1000, 9999 ) . '@noemail.nardone';
        $data['user_email'] = sanitize_email( $fake_email );
    }

    return $data;
}
add_filter( 'woocommerce_new_customer_data', 'nardone_customize_new_customer_data', 10, 1 );

/**
 * قبل از هر چیز، اگر فرم ثبت‌نام ووکامرس ارسال شده و ایمیل خالی است،
 * یک ایمیل فیک براساس موبایل در $_POST['email'] ست می‌کنیم.
 */
function nardone_force_fake_email_on_registration() {

    // فقط درخواست‌های POST
    $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : '';
    if ( 'POST' !== $method ) {
        return;
    }

    // فقط در فرانت‌اند
    if ( is_admin() ) {
        return;
    }

    // اگر کاربر لاگین است، کاری نکن
    if ( is_user_logged_in() ) {
        return;
    }

    // فقط اگر فرم ثبت‌نام ووکامرس ارسال شده:
    // وجود nonce ثبت‌نام یا دکمه register
    $has_wc_register = ! empty( $_POST['woocommerce-register-nonce'] ) || ! empty( $_POST['register'] );
    if ( ! $has_wc_register ) {
        return;
    }

    // اگر ایمیل قبلا پر شده، دخالت نکنیم
    if ( ! empty( $_POST['email'] ) ) {
        return;
    }

    // اگر موبایل نداریم، ایمیل فیک نسازیم (بگذار خطای عادی ووکامرس کار کند)
    if ( empty( $_POST['billing_phone'] ) ) {
        return;
    }

    // ساخت ایمیل فیک براساس موبایل
    $phone_digits = preg_replace( '/\D/', '', nardone_normalize_phone_digits( $_POST['billing_phone'] ) );
    if ( empty( $phone_digits ) ) {
        $phone_digits = (string) wp_rand( 10000000, 99999999 );
    }

    $fake_email = 'u' . $phone_digits . '+' . wp_rand( 1000, 9999 ) . '@noemail.nardone';
    $fake_email = sanitize_email( $fake_email );

    $_POST['email']    = $fake_email;
    $_REQUEST['email'] = $fake_email;
}
add_action( 'init', 'nardone_force_fake_email_on_registration', 1 );

/**
 * اعتبارسنجی فیلدهای ثبت‌نام (شامل چک کردن OTP)
 */
function nardone_validate_registration_fields( $username, $email, $validation_errors ) {

    // ---------------- موبایل (برای ولیدیشن و OTP) ----------------
    $phone = null;
    if ( ! empty( $_POST['billing_phone'] ) ) {
        $phone = nardone_normalize_phone_digits( $_POST['billing_phone'] );
    }

    // حذف خطاهای مربوط به ایمیل در این مرحله (اگر ووکامرس قبلا چیزی اضافه کرده)
    if ( method_exists( $validation_errors, 'remove' ) ) {
        $validation_errors->remove( 'registration-error-email-required' );
        $validation_errors->remove( 'registration-error-invalid-email' );
    } else {
        if ( isset( $validation_errors->errors['registration-error-email-required'] ) ) {
            unset( $validation_errors->errors['registration-error-email-required'] );
        }
        if ( isset( $validation_errors->errors['registration-error-invalid-email'] ) ) {
            unset( $validation_errors->errors['registration-error-invalid-email'] );
        }
    }

    // ---------------- نام و نام خانوادگی ----------------
    if ( empty( $_POST['billing_first_name'] ) ) {
        $validation_errors->add( 'billing_first_name_error', 'لطفا نام خود را وارد کنید.' );
    }

    if ( empty( $_POST['billing_last_name'] ) ) {
        $validation_errors->add( 'billing_last_name_error', 'لطفا نام خانوادگی خود را وارد کنید.' );
    }

    // ---------------- موبایل ----------------
    if ( empty( $_POST['billing_phone'] ) ) {
        $validation_errors->add( 'billing_phone_error', 'لطفا شماره موبایل خود را وارد کنید.' );
    } else {
        if ( ! preg_match( '/^09[0-9]{9}$/', $phone ) ) {
            $validation_errors->add( 'billing_phone_format_error', 'شماره موبایل معتبر نیست. مثال: 09121234567' );
        } else {
            // بررسی تکراری نبودن موبایل
            $users = get_users( array(
                'meta_key'   => 'billing_phone',
                'meta_value' => $phone,
                'number'     => 1,
                'fields'     => 'ID',
            ) );

            if ( ! empty( $users ) ) {
                $validation_errors->add( 'billing_phone_exists', 'با این شماره موبایل قبلا ثبت‌نام انجام شده است.' );
            }
        }
    }

    // ---------------- OTP ----------------
    if ( empty( $_POST['nardone_otp_code'] ) ) {
        $validation_errors->add( 'nardone_otp_code_error', 'لطفا کد تأیید (OTP) را وارد کنید.' );
    } else {
        $otp_input = nardone_normalize_phone_digits( $_POST['nardone_otp_code'] );

        if ( ! preg_match( '/^[0-9]{4,8}$/', $otp_input ) ) {
            $validation_errors->add( 'nardone_otp_code_format', 'فرمت کد تأیید معتبر نیست.' );
        } else {
            if ( $phone ) {
                $otp_key  = 'nardone_otp_' . md5( $phone );
                $otp_data = get_transient( $otp_key );

                if ( ! $otp_data || ! is_array( $otp_data ) ) {
                    $validation_errors->add( 'nardone_otp_not_found', 'کد تأیید برای این شماره یافت نشد یا منقضی شده است. لطفا دوباره کد را دریافت کنید.' );
                } else {
                    if ( empty( $otp_data['code'] ) || empty( $otp_data['expires'] ) || empty( $otp_data['phone'] ) ) {
                        $validation_errors->add( 'nardone_otp_invalid_data', 'خطا در اعتبارسنجی کد تأیید. لطفا دوباره تلاش کنید.' );
                    } else {
                        if ( $otp_data['phone'] !== $phone ) {
                            $validation_errors->add( 'nardone_otp_phone_mismatch', 'کد تأیید مربوط به این شماره موبایل نیست.' );
                        } elseif ( time() > (int) $otp_data['expires'] ) {
                            $validation_errors->add( 'nardone_otp_expired', 'کد تأیید منقضی شده است. لطفا دوباره کد را دریافت کنید.' );
                        } elseif ( (string) $otp_input !== (string) $otp_data['code'] ) {
                            $validation_errors->add( 'nardone_otp_wrong', 'کد تأیید وارد شده صحیح نیست.' );
                        } else {
                            // کد صحیح است، ترنزینت را حذف می‌کنیم
                            delete_transient( $otp_key );
                        }
                    }
                }
            } else {
                $validation_errors->add( 'nardone_otp_no_phone', 'ابتدا شماره موبایل معتبر را وارد کنید.' );
            }
        }
    }

    return $validation_errors;
}
add_filter( 'woocommerce_register_post', 'nardone_validate_registration_fields', 10, 3 );

/**
 * حذف نهایی خطاهای مربوط به ایمیل در ثبت‌نام ووکامرس
 */
function nardone_remove_email_errors_on_registration( $errors, $username, $email ) {

    if ( method_exists( $errors, 'remove' ) ) {
        $errors->remove( 'registration-error-email-required' );
        $errors->remove( 'registration-error-invalid-email' );
    } else {
        if ( isset( $errors->errors['registration-error-email-required'] ) ) {
            unset( $errors->errors['registration-error-email-required'] );
        }
        if ( isset( $errors->errors['registration-error-invalid-email'] ) ) {
            unset( $errors->errors['registration-error-invalid-email'] );
        }
    }

    return $errors;
}
add_filter( 'woocommerce_registration_errors', 'nardone_remove_email_errors_on_registration', 99, 3 );

/**
 * ذخیره فیلدهای سفارشی بعد از ساخت مشتری ووکامرس
 */
function nardone_save_registration_fields( $customer_id ) {

    if ( isset( $_POST['billing_first_name'] ) ) {
        $first_name = sanitize_text_field( wp_unslash( $_POST['billing_first_name'] ) );
        update_user_meta( $customer_id, 'first_name', $first_name );
        update_user_meta( $customer_id, 'billing_first_name', $first_name );
    }

    if ( isset( $_POST['billing_last_name'] ) ) {
        $last_name = sanitize_text_field( wp_unslash( $_POST['billing_last_name'] ) );
        update_user_meta( $customer_id, 'last_name', $last_name );
        update_user_meta( $customer_id, 'billing_last_name', $last_name );
    }

    if ( isset( $_POST['billing_phone'] ) ) {
        $phone = nardone_normalize_phone_digits( $_POST['billing_phone'] );
        update_user_meta( $customer_id, 'billing_phone', $phone );
    }

    // موبایل تایید شده است
    update_user_meta( $customer_id, 'nardone_mobile_verified', 1 );
}
add_action( 'woocommerce_created_customer', 'nardone_save_registration_fields', 10, 1 );

/**
 * مخفی کردن فیلد ایمیل در فرم ثبت‌نام صفحه حساب کاربری
 */
function nardone_hide_email_field_css() {
    if ( is_account_page() ) {
        echo '<style>
        .woocommerce form.register p.form-row-wide label[for="reg_email"],
        .woocommerce form.register p.form-row-wide input#reg_email {
            display: none !important;
        }
        </style>';
    }
}
add_action( 'wp_head', 'nardone_hide_email_field_css' );

/**
 * اسکریپت‌های فرانت‌اند: جابه‌جایی فیلد پسورد و هندل کردن ارسال OTP
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

        // تبدیل اعداد فارسی/عربی به لاتین
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

        // نرمال‌سازی OTP هنگام blur
        $('#reg_nardone_otp_code').on('blur', function() {
            var val = $(this).val();
            val = nardoneNormalizeDigits(val);
            $(this).val(val);
        });

        var \$form = $('form.register');
        if (!\$form.length) {
            return;
        }

        // جابه‌جایی پسورد زیر OTP
        var \$passwordRow = \$form.find('#reg_password').closest('p');
        var \$otpRow      = \$form.find('#reg_nardone_otp_code').closest('p');

        if (\$passwordRow.length && \$otpRow.length) {
            \$passwordRow.insertAfter(\$otpRow);
        }

        // هندل کردن کلیک روی دکمه ارسال OTP
        $('#nardone_send_otp_btn').on('click', function(e) {
            e.preventDefault();

            var \$btn     = $(this);
            var phoneRaw  = $.trim( $('#reg_billing_phone').val() );
            var phone     = nardoneNormalizeDigits(phoneRaw);
            $('#reg_billing_phone').val(phone);

            if (!phone) {
                alert('لطفا ابتدا شماره موبایل را وارد کنید.');
                return;
            }

            var phoneRegex = /^09[0-9]{9}$/;
            if (!phoneRegex.test(phone)) {
                alert('شماره موبایل معتبر نیست. مثال: 09121234567');
                return;
            }

            if (\$btn.data('sending')) {
                return;
            }
            \$btn.data('sending', true);
            var oldText = \$btn.text();
            \$btn.text('در حال ارسال...').prop('disabled', true);

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
                    alert(response.data && response.data.message ? response.data.message : 'کد تأیید ارسال شد.');
                } else {
                    var msg = (response && response.data && response.data.message)
                        ? response.data.message
                        : 'خطا در ارسال کد تأیید. لطفا دوباره تلاش کنید.';
                    alert(msg);
                }
            })
            .fail(function() {
                alert('خطای ارتباط با سرور. لطفا بعدا دوباره تلاش کنید.');
            })
            .always(function() {
                \$btn.data('sending', false);
                \$btn.text(oldText).prop('disabled', false);
            });
        });

    });
    ";

    wp_add_inline_script( 'nardone-frontend', $script );
}
add_action( 'wp_enqueue_scripts', 'nardone_frontend_scripts' );

/**
 * Ajax: ارسال کد OTP به موبایل (استفاده از تنظیمات پنل)
 */
function nardone_send_otp_ajax() {

    if ( ! isset( $_POST['nonce'], $_POST['phone'] ) ) {
        wp_send_json_error( array(
            'message' => 'درخواست نامعتبر است.',
        ) );
    }

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'nardone_send_otp' ) ) {
        wp_send_json_error( array(
            'message' => 'خطای امنیتی. لطفا صفحه را رفرش کنید.',
        ) );
    }

    $api_key       = trim( (string) get_option( 'nardone_otp_api_key', '' ) );
    $pattern_code  = trim( (string) get_option( 'nardone_otp_pattern_code', '' ) );
    $from_number   = trim( (string) get_option( 'nardone_otp_from_number', '' ) );
    $pattern_param = trim( (string) get_option( 'nardone_otp_pattern_param', 'otp_code' ) );

    if ( empty( $api_key ) || empty( $pattern_code ) || empty( $from_number ) || empty( $pattern_param ) ) {
        wp_send_json_error( array(
            'message' => 'تنظیمات OTP به‌درستی انجام نشده است. لطفا با مدیر سایت تماس بگیرید.',
        ) );
    }

    $phone = nardone_normalize_phone_digits( $_POST['phone'] );

    if ( ! preg_match( '/^09[0-9]{9}$/', $phone ) ) {
        wp_send_json_error( array(
            'message' => 'شماره موبایل معتبر نیست. مثال: 09121234567',
        ) );
    }

    $otp_key  = 'nardone_otp_' . md5( $phone );
    $existing = get_transient( $otp_key );

    if ( $existing && isset( $existing['last_sent'] ) && ( time() - (int) $existing['last_sent'] ) < 60 ) {
        wp_send_json_error( array(
            'message' => 'کد تأیید قبلا ارسال شده است. کمی صبر کنید و دوباره تلاش کنید.',
        ) );
    }

    $otp_code = wp_rand( 100000, 999999 );

    $data = array(
        'code'      => (string) $otp_code,
        'phone'     => $phone,
        'expires'   => time() + NARDONE_OTP_EXPIRY,
        'last_sent' => time(),
    );
    set_transient( $otp_key, $data, NARDONE_OTP_EXPIRY + 60 );

    $body = array(
        'sending_type' => 'pattern',
        'from_number'  => $from_number,
        'code'         => $pattern_code,
        'recipients'   => array( $phone ),
        'params'       => array(
            $pattern_param => (string) $otp_code,
        ),
    );

    $response = wp_remote_post( NARDONE_OTP_API_URL, array(
        'headers' => array(
            'Authorization' => $api_key,
            'Content-Type'  => 'application/json',
        ),
        'body'    => wp_json_encode( $body ),
        'timeout' => 15,
    ) );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array(
            'message' => 'خطا در ارتباط با سرویس پیامک. لطفا بعدا دوباره تلاش کنید.',
        ) );
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $resp_body   = wp_remote_retrieve_body( $response );
    $resp_json   = json_decode( $resp_body, true );

    if ( $status_code < 200 || $status_code >= 300 ) {
        $msg = 'ارسال پیامک با خطا مواجه شد.';
        if ( is_array( $resp_json ) && ! empty( $resp_json['message'] ) ) {
            $msg .= ' ' . $resp_json['message'];
        }

        wp_send_json_error( array(
            'message' => $msg,
        ) );
    }

    wp_send_json_success( array(
        'message' => 'کد تأیید با موفقیت ارسال شد.',
    ) );
}
add_action( 'wp_ajax_nardone_send_otp',        'nardone_send_otp_ajax' );
add_action( 'wp_ajax_nopriv_nardone_send_otp', 'nardone_send_otp_ajax' );

/**
 * اجازه‌ی استفاده از هر سطح سختی رمز (فقط نمایش نوار سختی، بدون جلوگیری از ثبت‌نام)
 */
function nardone_allow_any_password_strength( $min_strength ) {
    return 0;
}
add_filter( 'woocommerce_min_password_strength', 'nardone_allow_any_password_strength', 10, 1 );
<?php
/**
 * OTP-based login functionality (replaces password authentication)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display OTP-only login UI on the My Account login page
 * and hide the default username/password form (no-password login).
 */
function nardone_render_otp_login_ui() {
    if ( is_user_logged_in() || ! is_account_page() ) {
        return;
    }

    $ajax_url      = esc_url( admin_url( 'admin-ajax.php' ) );
    $nonce_send    = wp_create_nonce( 'nardone_send_login_otp' );
    $nonce_verify  = wp_create_nonce( 'nardone_verify_login_otp' );
    $redirect_url  = esc_url_raw( wc_get_page_permalink( 'myaccount' ) );
    ?>
    <div class="nardone-otp-login-container">
        <h2><?php esc_html_e( 'ورود با کد تأیید', 'nardone' ); ?></h2>
        <p><?php esc_html_e( 'شماره موبایل خود را وارد کنید، کد پیامکی دریافت کنید و بدون رمز وارد شوید.', 'nardone' ); ?></p>

        <form id="nardone_otp_login_form" class="nardone-otp-login-form" method="post" novalidate>
            <p class="form-row form-row-wide">
                <label for="nardone_login_phone"><?php esc_html_e( 'شماره موبایل', 'nardone' ); ?> <span class="required">*</span></label>
                <input type="tel" class="input-text" name="nardone_login_phone" id="nardone_login_phone" placeholder="09121234567" autocomplete="tel" />
            </p>

            <p class="form-row form-row-wide">
                <label for="nardone_login_otp_code"><?php esc_html_e( 'کد تأیید پیامکی', 'nardone' ); ?> <span class="required">*</span></label>
                <div class="nardone-otp-row">
                    <input type="text" class="input-text" name="nardone_login_otp_code" id="nardone_login_otp_code" placeholder="123456" inputmode="numeric" />
                    <button type="button" class="button" id="nardone_login_send_otp_btn"><?php esc_html_e( 'ارسال کد', 'nardone' ); ?></button>
                </div>
            </p>

            <p class="form-row">
                <button type="submit" class="button button-primary" id="nardone_login_submit_btn">
                    <?php esc_html_e( 'ورود بدون رمز', 'nardone' ); ?>
                </button>
            </p>

            <div id="nardone_login_otp_message" class="nardone-message" style="display:none;"></div>
        </form>
    </div>

    <style>
        /* Hide WooCommerce default login form to enforce OTP-only login */
        .woocommerce form.login { display: none !important; }
        .nardone-otp-login-container {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #e5e5e5;
        }
        .nardone-otp-row {
            display: flex;
            gap: 8px;
        }
        .nardone-otp-row input { flex: 1; }
        .nardone-message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
        }
        .nardone-message.error {
            background: #fee;
            color: #c00;
            border: 1px solid #fcc;
            display: block;
        }
        .nardone-message.success {
            background: #efe;
            color: #0c0;
            border: 1px solid #cfc;
            display: block;
        }
    </style>

    <script type="text/javascript">
    jQuery(function($) {
        var ajaxUrl     = <?php echo wp_json_encode( $ajax_url ); ?>;
        var nonceSend   = <?php echo wp_json_encode( $nonce_send ); ?>;
        var nonceVerify = <?php echo wp_json_encode( $nonce_verify ); ?>;
        var redirectUrl = <?php echo wp_json_encode( $redirect_url ); ?>;

        function normalizeDigits(str) {
            if (!str) return '';
            var persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            var arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
            for (var i = 0; i < 10; i++) {
                var p = new RegExp(persian[i], 'g');
                var a = new RegExp(arabic[i], 'g');
                str = str.replace(p, i).replace(a, i);
            }
            return str.replace(/\s+/g, '');
        }

        function showMessage(type, text) {
            var $box = $('#nardone_login_otp_message');
            $box.removeClass('error success').addClass(type).text(text).show();
        }

        $('#nardone_login_send_otp_btn').on('click', function(e) {
            e.preventDefault();

            var $btn  = $(this);
            var phone = normalizeDigits( $('#nardone_login_phone').val() );
            $('#nardone_login_phone').val(phone);

            if (!phone) {
                showMessage('error', <?php echo wp_json_encode( __( 'لطفاً شماره موبایل را وارد کنید.', 'nardone' ) ); ?>);
                return;
            }

            var phoneRegex = /^09[0-9]{9}$/;
            if (!phoneRegex.test(phone)) {
                showMessage('error', <?php echo wp_json_encode( __( 'شماره موبایل معتبر نیست. مثال: 09121234567', 'nardone' ) ); ?>);
                return;
            }

            if ($btn.data('sending')) {
                return;
            }

            $btn.data('sending', true);
            var oldText = $btn.text();
            $btn.prop('disabled', true).text(<?php echo wp_json_encode( __( 'در حال ارسال...', 'nardone' ) ); ?>);

            $.post(ajaxUrl, {
                action: 'nardone_send_login_otp',
                phone: phone,
                nonce: nonceSend
            }).done(function(response) {
                if (response && response.success) {
                    showMessage('success', response.data && response.data.message ? response.data.message : <?php echo wp_json_encode( __( 'کد تأیید ارسال شد.', 'nardone' ) ); ?>);
                } else {
                    var msg = (response && response.data && response.data.message) ? response.data.message : <?php echo wp_json_encode( __( 'ارسال کد با خطا مواجه شد.', 'nardone' ) ); ?>;
                    showMessage('error', msg);
                }
            }).fail(function() {
                showMessage('error', <?php echo wp_json_encode( __( 'خطا در ارتباط با سرور. دوباره تلاش کنید.', 'nardone' ) ); ?>);
            }).always(function() {
                $btn.data('sending', false);
                $btn.prop('disabled', false).text(oldText);
            });
        });

        $('#nardone_otp_login_form').on('submit', function(e) {
            e.preventDefault();

            var $submit = $('#nardone_login_submit_btn');
            var phone   = normalizeDigits( $('#nardone_login_phone').val() );
            var code    = normalizeDigits( $('#nardone_login_otp_code').val() );

            if (!phone || !/^09[0-9]{9}$/.test(phone)) {
                showMessage('error', <?php echo wp_json_encode( __( 'شماره موبایل معتبر نیست.', 'nardone' ) ); ?>);
                return;
            }

            if (!code || !/^[0-9]{4,8}$/.test(code)) {
                showMessage('error', <?php echo wp_json_encode( __( 'کد تأیید معتبر نیست.', 'nardone' ) ); ?>);
                return;
            }

            if ($submit.data('loading')) {
                return;
            }

            $submit.data('loading', true).prop('disabled', true);

            $.post(ajaxUrl, {
                action: 'nardone_verify_login_otp',
                phone: phone,
                otp_code: code,
                nonce: nonceVerify
            }).done(function(response) {
                if (response && response.success) {
                    showMessage('success', response.data && response.data.message ? response.data.message : <?php echo wp_json_encode( __( 'ورود موفق بود.', 'nardone' ) ); ?>);
                    window.location.href = redirectUrl;
                } else {
                    var msg = (response && response.data && response.data.message) ? response.data.message : <?php echo wp_json_encode( __( 'کد صحیح نیست یا منقضی شده است.', 'nardone' ) ); ?>;
                    showMessage('error', msg);
                }
            }).fail(function() {
                showMessage('error', <?php echo wp_json_encode( __( 'خطای سرور. لطفاً دوباره تلاش کنید.', 'nardone' ) ); ?>);
            }).always(function() {
                $submit.data('loading', false).prop('disabled', false);
            });
        });
    });
    </script>
    <?php
}
add_action( 'woocommerce_before_customer_login_form', 'nardone_render_otp_login_ui', 5 );

/**
 * AJAX handler for sending OTP to login phone number
 */
function nardone_ajax_send_login_otp() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nardone_send_login_otp' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nardone' ) ) );
    }

    $phone = isset( $_POST['phone'] ) ? nardone_normalize_phone_digits( sanitize_text_field( wp_unslash( $_POST['phone'] ) ) ) : '';

    if ( empty( $phone ) || ! nardone_is_valid_mobile( $phone ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid mobile number.', 'nardone' ) ) );
    }

    // Check if phone is registered
    $users = get_users( array(
        'meta_key'   => 'billing_phone',
        'meta_value' => $phone,
        'number'     => 1,
        'fields'     => 'ID',
    ) );

    if ( empty( $users ) ) {
        wp_send_json_error( array( 'message' => __( 'This phone number is not registered.', 'nardone' ) ) );
    }

    // Check rate limit
    $otp_key     = 'nardone_otp_' . md5( $phone );
    $otp_data    = get_transient( $otp_key );
    $rate_limit  = 60;

    if ( $otp_data && isset( $otp_data['last_sent'] ) && ( time() - $otp_data['last_sent'] ) < $rate_limit ) {
        $wait_time = $rate_limit - ( time() - $otp_data['last_sent'] );
        wp_send_json_error( array( 'message' => sprintf( __( 'Please wait %d seconds before requesting a new code.', 'nardone' ), $wait_time ) ) );
    }

    // Generate OTP
    $otp_code = wp_rand( 100000, 999999 );
    $otp_data = array(
        'code'       => (string) $otp_code,
        'phone'      => $phone,
        'expires'    => time() + ( 3 * MINUTE_IN_SECONDS ),
        'last_sent'  => time(),
    );

    set_transient( $otp_key, $otp_data, 3 * MINUTE_IN_SECONDS );

    // Send OTP via IPPanel
    $api_key        = get_option( NARDONE_OPT_API_KEY );
    $pattern_code   = get_option( NARDONE_OPT_PATTERN_CODE );
    $from_number    = get_option( NARDONE_OPT_FROM_NUMBER );
    $pattern_param  = get_option( NARDONE_OPT_PATTERN_PARAM, 'otp_code' );

    if ( ! $api_key || ! $pattern_code ) {
        wp_send_json_error( array( 'message' => __( 'SMS gateway is not configured.', 'nardone' ) ) );
    }

    $response = wp_safe_remote_post(
        'https://edge.ippanel.com/v1/api/send',
        array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( array(
                'sending_type' => 'pattern',
                'from_number'  => $from_number,
                'code'         => $pattern_code,
                'recipients'   => array( $phone ),
                'params'       => array( $pattern_param => (string) $otp_code ),
            ) ),
        )
    );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => __( 'Failed to send OTP. Please try again.', 'nardone' ) ) );
    }

    wp_send_json_success( array( 'message' => __( 'Verification code sent to your phone.', 'nardone' ) ) );
}
add_action( 'wp_ajax_nardone_send_login_otp', 'nardone_ajax_send_login_otp' );
add_action( 'wp_ajax_nopriv_nardone_send_login_otp', 'nardone_ajax_send_login_otp' );

/**
 * AJAX handler for verifying login OTP and logging user in
 */
function nardone_ajax_verify_login_otp() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nardone_verify_login_otp' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nardone' ) ) );
    }

    $phone     = isset( $_POST['phone'] ) ? nardone_normalize_phone_digits( sanitize_text_field( wp_unslash( $_POST['phone'] ) ) ) : '';
    $otp_input = isset( $_POST['otp_code'] ) ? nardone_normalize_phone_digits( sanitize_text_field( wp_unslash( $_POST['otp_code'] ) ) ) : '';

    if ( empty( $phone ) || ! nardone_is_valid_mobile( $phone ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid mobile number.', 'nardone' ) ) );
    }

    if ( empty( $otp_input ) || ! preg_match( '/^[0-9]{4,8}$/', $otp_input ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid verification code format.', 'nardone' ) ) );
    }

    // Verify OTP
    $otp_key  = 'nardone_otp_' . md5( $phone );
    $otp_data = get_transient( $otp_key );

    if ( ! $otp_data || ! is_array( $otp_data ) ) {
        wp_send_json_error( array( 'message' => __( 'Verification code not found or expired.', 'nardone' ) ) );
    }

    if ( $otp_data['phone'] !== $phone ) {
        wp_send_json_error( array( 'message' => __( 'Verification code does not match the phone number.', 'nardone' ) ) );
    }

    if ( time() > (int) $otp_data['expires'] ) {
        wp_send_json_error( array( 'message' => __( 'Verification code has expired.', 'nardone' ) ) );
    }

    if ( (string) $otp_input !== (string) $otp_data['code'] ) {
        wp_send_json_error( array( 'message' => __( 'Incorrect verification code.', 'nardone' ) ) );
    }

    // Clear OTP
    delete_transient( $otp_key );

    // Find and login user
    $users = get_users( array(
        'meta_key'   => 'billing_phone',
        'meta_value' => $phone,
        'number'     => 1,
        'fields'     => 'ID',
    ) );

    if ( empty( $users ) ) {
        wp_send_json_error( array( 'message' => __( 'User not found.', 'nardone' ) ) );
    }

    $user_id = $users[0];
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id );

    wp_send_json_success( array( 'message' => __( 'Login successful!', 'nardone' ) ) );
}
add_action( 'wp_ajax_nardone_verify_login_otp', 'nardone_ajax_verify_login_otp' );
add_action( 'wp_ajax_nopriv_nardone_verify_login_otp', 'nardone_ajax_verify_login_otp' );

/**
 * Change login form labels
 */
function nardone_change_login_labels( $translated_text, $text, $domain ) {
    if ( $domain === 'woocommerce' ) {
        switch ( $text ) {
            case 'Username or email address':
            case 'Username or email':
                return 'شماره موبایل یا نام کاربری';
            case 'Password':
                return 'رمز عبور';
            case 'Remember me':
                return 'مرا به خاطر بسپار';
            case 'Log in':
                return 'ورود';
            case 'Lost your password?':
                return 'رمز عبور خود را فراموش کرده‌اید؟';
        }
    }
    return $translated_text;
}
add_filter( 'gettext', 'nardone_change_login_labels', 20, 3 );
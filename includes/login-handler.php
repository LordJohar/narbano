<?php
/**
 * OTP-based login functionality (replaces password authentication)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display OTP login form on My Account page for non-logged-in users
 */
function nardone_show_otp_login_form_on_account() {
    if ( is_user_logged_in() || ! is_account_page() ) {
        return;
    }

    ?>
    <div class="nardone-otp-login-container">
        <h2><?php esc_html_e( 'Login with Mobile OTP', 'nardone' ); ?></h2>
        <p><?php esc_html_e( 'Enter your phone number and receive a verification code via SMS.', 'nardone' ); ?></p>
        
        <form id="nardone_otp_login_form" method="post">
            <p class="form-row form-row-wide">
                <label for="nardone_login_phone"><?php esc_html_e( 'Mobile number', 'nardone' ); ?>&nbsp;<span class="required">*</span></label>
                <input type="text" class="input-text" name="nardone_login_phone" id="nardone_login_phone" placeholder="09121234567" />
            </p>

            <p class="form-row form-row-wide">
                <label for="nardone_login_otp_code"><?php esc_html_e( 'Verification code (OTP)', 'nardone' ); ?>&nbsp;<span class="required">*</span></label>
                <div class="nardone-otp-row">
                    <input type="text" class="input-text" name="nardone_login_otp_code" id="nardone_login_otp_code" />
                    <button type="button" class="button" id="nardone_login_send_otp_btn">
                        <?php esc_html_e( 'دریافت کد', 'nardone' ); ?>
                    </button>
                </div>
            </p>

            <p class="form-row">
                <?php wp_nonce_field( 'nardone_otp_login', 'nardone_otp_login_nonce' ); ?>
                <button type="submit" class="button" name="login" value="<?php esc_attr_e( 'Login', 'nardone' ); ?>">
                    <?php esc_html_e( 'Login', 'nardone' ); ?>
                </button>
            </p>

            <div id="nardone_login_otp_message" class="nardone-message" style="display:none;"></div>
        </form>

        <style>
        .nardone-otp-login-container {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
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
        jQuery(document).ready(function($) {
            $('#nardone_login_send_otp_btn').on('click', function(e) {
                e.preventDefault();
                var phone = $('#nardone_login_phone').val();
                
                if (!phone) {
                    alert('<?php esc_js_e( 'Please enter your phone number.', 'nardone' ); ?>');
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                    data: {
                        action: 'nardone_send_login_otp',
                        phone: phone,
                        nonce: '<?php echo wp_create_nonce( 'nardone_send_login_otp' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#nardone_login_otp_message').removeClass('error').addClass('success').text(response.data.message).show();
                        } else {
                            $('#nardone_login_otp_message').removeClass('success').addClass('error').text(response.data.message).show();
                        }
                    }
                });
            });

            $('#nardone_otp_login_form').on('submit', function(e) {
                e.preventDefault();
                var phone = $('#nardone_login_phone').val();
                var otp_code = $('#nardone_login_otp_code').val();
                
                $.ajax({
                    type: 'POST',
                    url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                    data: {
                        action: 'nardone_verify_login_otp',
                        phone: phone,
                        otp_code: otp_code,
                        nonce: '<?php echo wp_create_nonce( 'nardone_verify_login_otp' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '<?php echo wc_get_page_permalink( 'myaccount' ); ?>';
                        } else {
                            $('#nardone_login_otp_message').removeClass('success').addClass('error').text(response.data.message).show();
                        }
                    }
                });
            });
        });
        </script>
    </div>
    <?php
}
add_action( 'woocommerce_account_content', 'nardone_show_otp_login_form_on_account', 5 );

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
<?php
/**
 * Admin settings page for OTP configuration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

/**
 * Add settings page to WordPress options menu.
 */
function nardone_add_settings_page() {
    add_options_page(
        __( 'Nardone OTP Settings', 'nardone' ),
        'Nardone OTP',
        'manage_options',
        'nardone-otp-settings',
        'nardone_render_settings_page'
    );
}
add_action( 'admin_menu', 'nardone_add_settings_page' );

/**
 * Render settings page markup and handle form submission.
 */
function nardone_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['nardone_otp_save_settings'] ) ) {
        check_admin_referer( 'nardone_otp_save_settings_nonce' );

        $api_key       = isset( $_POST['nardone_otp_api_key'] ) ? trim( wp_unslash( $_POST['nardone_otp_api_key'] ) ) : '';
        $pattern_code  = isset( $_POST['nardone_otp_pattern_code'] ) ? trim( wp_unslash( $_POST['nardone_otp_pattern_code'] ) ) : '';
        $from_number   = isset( $_POST['nardone_otp_from_number'] ) ? trim( wp_unslash( $_POST['nardone_otp_from_number'] ) ) : '';
        $pattern_param = isset( $_POST['nardone_otp_pattern_param'] ) ? trim( wp_unslash( $_POST['nardone_otp_pattern_param'] ) ) : '';

        update_option( NARDONE_OPT_API_KEY,       sanitize_text_field( $api_key ) );
        update_option( NARDONE_OPT_PATTERN_CODE,  sanitize_text_field( $pattern_code ) );
        update_option( NARDONE_OPT_FROM_NUMBER,   sanitize_text_field( $from_number ) );
        update_option( NARDONE_OPT_PATTERN_PARAM, sanitize_key( $pattern_param ?: 'otp_code' ) );

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'nardone' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    $api_key       = get_option( NARDONE_OPT_API_KEY, '' );
    $pattern_code  = get_option( NARDONE_OPT_PATTERN_CODE, '' );
    $from_number   = get_option( NARDONE_OPT_FROM_NUMBER, '' );
    $pattern_param = get_option( NARDONE_OPT_PATTERN_PARAM, 'otp_code' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Nardone OTP Settings', 'nardone' ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'nardone_otp_save_settings_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="nardone_otp_api_key">API Key</label></th>
                        <td>
                            <input type="text" id="nardone_otp_api_key" name="nardone_otp_api_key" class="regular-text" value="<?php echo esc_attr( $api_key ); ?>" />
                            <p class="description"><?php esc_html_e( 'API key provided by IPPanel (Authorization header).', 'nardone' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="nardone_otp_pattern_code">Pattern Code</label></th>
                        <td>
                            <input type="text" id="nardone_otp_pattern_code" name="nardone_otp_pattern_code" class="regular-text" value="<?php echo esc_attr( $pattern_code ); ?>" />
                            <p class="description"><?php esc_html_e( 'Pattern code created in IPPanel (e.g., zvtfo5o4badaang).', 'nardone' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="nardone_otp_from_number"><?php esc_html_e( 'Sender number', 'nardone' ); ?></label></th>
                        <td>
                            <input type="text" id="nardone_otp_from_number" name="nardone_otp_from_number" class="regular-text" value="<?php echo esc_attr( $from_number ); ?>" />
                            <p class="description"><?php esc_html_e( 'Example: +983000505', 'nardone' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="nardone_otp_pattern_param"><?php esc_html_e( 'Pattern variable name', 'nardone' ); ?></label></th>
                        <td>
                            <input type="text" id="nardone_otp_pattern_param" name="nardone_otp_pattern_param" class="regular-text" value="<?php echo esc_attr( $pattern_param ); ?>" />
                            <p class="description"><?php esc_html_e( 'Variable name used inside the pattern text (e.g., otp_code). OTP value is sent in this field.', 'nardone' ); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <button type="submit" name="nardone_otp_save_settings" class="button button-primary"><?php esc_html_e( 'Save changes', 'nardone' ); ?></button>
            </p>
        </form>
    </div>
    <?php
}

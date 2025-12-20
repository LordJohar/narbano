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
        'تنظیمات Nardone OTP',
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
    
    // Save settings
    if ( isset( $_POST['nardone_otp_save_settings'] ) ) {
        check_admin_referer( 'nardone_otp_save_settings_nonce' );
        
        update_option( 'nardone_otp_api_key', sanitize_text_field( $_POST['nardone_otp_api_key'] ?? '' ) );
        update_option( 'nardone_otp_pattern_code', sanitize_text_field( $_POST['nardone_otp_pattern_code'] ?? '' ) );
        update_option( 'nardone_otp_from_number', sanitize_text_field( $_POST['nardone_otp_from_number'] ?? '' ) );
        update_option( 'nardone_otp_pattern_param', sanitize_text_field( $_POST['nardone_otp_pattern_param'] ?? 'otp_code' ) );
        
        echo '<div class="notice notice-success"><p>تنظیمات ذخیره شد.</p></div>';
    }
    
    // Get current values
    $api_key       = get_option( 'nardone_otp_api_key', '' );
    $pattern_code  = get_option( 'nardone_otp_pattern_code', '' );
    $from_number   = get_option( 'nardone_otp_from_number', '' );
    $pattern_param = get_option( 'nardone_otp_pattern_param', 'otp_code' );
    ?>
    
    <div class="wrap">
        <h1>تنظیمات Nardone OTP</h1>
        <form method="post">
            <?php wp_nonce_field( 'nardone_otp_save_settings_nonce' ); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="nardone_otp_api_key">API Key</label></th>
                    <td>
                        <input type="text" id="nardone_otp_api_key" name="nardone_otp_api_key" 
                               class="regular-text" value="<?php echo esc_attr( $api_key ); ?>" />
                        <p class="description">کلید API از IPPanel</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="nardone_otp_pattern_code">Pattern Code</label></th>
                    <td>
                        <input type="text" id="nardone_otp_pattern_code" name="nardone_otp_pattern_code" 
                               class="regular-text" value="<?php echo esc_attr( $pattern_code ); ?>" />
                        <p class="description">کد پترن از IPPanel</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="nardone_otp_from_number">شماره فرستنده</label></th>
                    <td>
                        <input type="text" id="nardone_otp_from_number" name="nardone_otp_from_number" 
                               class="regular-text" value="<?php echo esc_attr( $from_number ); ?>" />
                        <p class="description">مثال: +983000505</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="nardone_otp_pattern_param">نام متغیر پترن</label></th>
                    <td>
                        <input type="text" id="nardone_otp_pattern_param" name="nardone_otp_pattern_param" 
                               class="regular-text" value="<?php echo esc_attr( $pattern_param ); ?>" />
                        <p class="description">مثال: otp_code</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="nardone_otp_save_settings" class="button button-primary">
                    ذخیره تغییرات
                </button>
            </p>
        </form>
    </div>
    <?php
}
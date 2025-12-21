<?php
/**
 * Registration validation including OTP checks.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

/**
 * Validate registration fields and OTP.
 *
 * @param string   $username Submitted username.
 * @param string   $email Submitted email.
 * @param WP_Error $validation_errors Validation object.
 * @return WP_Error
 */
function nardone_validate_registration_fields( $username, $email, $validation_errors ) {
    $phone = null;
    if ( ! empty( $_POST['billing_phone'] ) ) {
        $phone = nardone_normalize_phone_digits( $_POST['billing_phone'] );
    }

    // Remove email-related errors added by WooCommerce.
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

    // First/last name.
    if ( empty( $_POST['billing_first_name'] ) ) {
        $validation_errors->add( 'billing_first_name_error', __( 'Please enter your first name.', 'nardone' ) );
    }

    if ( empty( $_POST['billing_last_name'] ) ) {
        $validation_errors->add( 'billing_last_name_error', __( 'Please enter your last name.', 'nardone' ) );
    }

    // Phone.
    if ( empty( $_POST['billing_phone'] ) ) {
        $validation_errors->add( 'billing_phone_error', __( 'Please enter your mobile number.', 'nardone' ) );
    } else {
        if ( ! preg_match( '/^09[0-9]{9}$/', $phone ) ) {
            $validation_errors->add( 'billing_phone_format_error', __( 'Invalid mobile number. Example: 09121234567', 'nardone' ) );
        } else {
            $users = get_users( array(
                'meta_key'   => 'billing_phone',
                'meta_value' => $phone,
                'number'     => 1,
                'fields'     => 'ID',
            ) );

            if ( ! empty( $users ) ) {
                $validation_errors->add( 'billing_phone_exists', __( 'A user is already registered with this mobile number.', 'nardone' ) );
            }
        }
    }

    // OTP.
    if ( empty( $_POST['nardone_otp_code'] ) ) {
        $validation_errors->add( 'nardone_otp_code_error', __( 'Please enter the verification code (OTP).', 'nardone' ) );
    } else {
        $otp_input = nardone_normalize_phone_digits( $_POST['nardone_otp_code'] );

        if ( ! preg_match( '/^[0-9]{4,8}$/', $otp_input ) ) {
            $validation_errors->add( 'nardone_otp_code_format', __( 'Invalid verification code format.', 'nardone' ) );
        } else {
            if ( $phone ) {
                $otp_key  = 'nardone_otp_' . md5( $phone );
                $otp_data = get_transient( $otp_key );

                if ( ! $otp_data || ! is_array( $otp_data ) ) {
                    $validation_errors->add( 'nardone_otp_not_found', __( 'Verification code not found or expired. Please request a new code.', 'nardone' ) );
                } else {
                    if ( empty( $otp_data['code'] ) || empty( $otp_data['expires'] ) || empty( $otp_data['phone'] ) ) {
                        $validation_errors->add( 'nardone_otp_invalid_data', __( 'Verification code validation error. Please try again.', 'nardone' ) );
                    } else {
                        if ( $otp_data['phone'] !== $phone ) {
                            $validation_errors->add( 'nardone_otp_phone_mismatch', __( 'Verification code does not belong to this mobile number.', 'nardone' ) );
                        } elseif ( time() > (int) $otp_data['expires'] ) {
                            $validation_errors->add( 'nardone_otp_expired', __( 'Verification code has expired. Please request a new one.', 'nardone' ) );
                        } elseif ( (string) $otp_input !== (string) $otp_data['code'] ) {
                            $validation_errors->add( 'nardone_otp_wrong', __( 'Incorrect verification code.', 'nardone' ) );
                        } else {
                            // Valid code; clear transient.
                            delete_transient( $otp_key );
                        }
                    }
                }
            } else {
                $validation_errors->add( 'nardone_otp_no_phone', __( 'Enter a valid mobile number first.', 'nardone' ) );
            }
        }
    }

    return $validation_errors;
}
add_filter( 'woocommerce_register_post', 'nardone_validate_registration_fields', 10, 3 );

/**
 * Remove email and password errors on registration (OTP flow requires only phone).
 */
function nardone_remove_email_password_errors_on_registration( $errors, $username, $email ) {
    // Remove email errors (email hidden + fake email provided server-side)
    if ( method_exists( $errors, 'remove' ) ) {
        $errors->remove( 'registration-error-email-required' );
        $errors->remove( 'registration-error-invalid-email' );
    } else {
        unset( $errors->errors['registration-error-email-required'] );
        unset( $errors->errors['registration-error-invalid-email'] );
    }

    // Remove password errors (password field is removed + auto-generated)
    if ( method_exists( $errors, 'remove' ) ) {
        $errors->remove( 'registration-error-invalid-password' );
        $errors->remove( 'registration-error-password-required' );
        $errors->remove( 'registration-error-weak-password' );
    } else {
        unset( $errors->errors['registration-error-invalid-password'] );
        unset( $errors->errors['registration-error-password-required'] );
        unset( $errors->errors['registration-error-weak-password'] );
    }

    return $errors;
}
add_filter( 'woocommerce_registration_errors', 'nardone_remove_email_password_errors_on_registration', 99, 3 );

/**
 * Ensure WooCommerce always generates a password and never requires user input.
 */
add_filter( 'woocommerce_registration_generate_password', '__return_true', 20 );

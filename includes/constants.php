<?php
/**
 * Core constants for the Nardone Registration plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

// OTP settings.
define( 'NARDONE_OTP_API_URL', 'https://edge.ippanel.com/v1/api/send' );
define( 'NARDONE_OTP_EXPIRY',  3 * MINUTE_IN_SECONDS ); // OTP validity: 3 minutes

// Option keys (for clarity and reuse).
define( 'NARDONE_OPT_API_KEY',       'nardone_otp_api_key' );
define( 'NARDONE_OPT_PATTERN_CODE',  'nardone_otp_pattern_code' );
define( 'NARDONE_OPT_FROM_NUMBER',   'nardone_otp_from_number' );
define( 'NARDONE_OPT_PATTERN_PARAM', 'nardone_otp_pattern_param' );

# Nardone Registration

WooCommerce registration flow with mobile number, OTP verification, and automatic username generation. Built for IPPanel OTP delivery.

## Features
- Registration fields: first name, last name, mobile, OTP (email hidden on My Account page).
- OTP via IPPanel pattern endpoint with rate limiting and expiry (3 minutes by default).
- Auto-generated username (`nardoneXXXX`).
- Synthetic email fallback when none is provided (derived from phone).
- Phone uniqueness validation and OTP verification on submit.
- Password strength meter shown but not enforced.

## Requirements
- WordPress with WooCommerce active.
- IPPanel SMS account and a pattern code.

## Installation
1. Copy the plugin folder into your `wp-content/plugins/` directory.
2. Activate **Nardone Registration** in WordPress admin.
3. Ensure WooCommerce is active.

## Configuration
1. Go to **Settings → Nardone OTP**.
2. Fill in:
	- **API Key**: IPPanel API key (Authorization header).
	- **Pattern Code**: Your IPPanel pattern code.
	- **Sender number**: e.g., `+983000505`.
	- **Pattern variable name**: Variable in your pattern that holds the OTP (default: `otp_code`).
3. Save changes.

## How it works
- Users register via the WooCommerce account page.
- They request an OTP to their mobile; the code is stored in a transient with a 3-minute expiry.
- On submission, the plugin validates name, phone format/uniqueness, and OTP correctness/expiry.
- If email is missing, a synthetic email is generated from the phone number to satisfy WooCommerce.

## File structure
- `nardone-registration.php`: Plugin bootstrap; loads modules.
- `includes/constants.php`: Core constants and option keys.
- `includes/helpers.php`: Utilities (digit normalization, username generation).
- `includes/dependencies.php`: WooCommerce dependency check.
- `includes/admin-settings.php`: Settings page rendering/processing.
- `includes/registration-form.php`: Form fields and email field hiding.
- `includes/customer-data.php`: Fake email injection and user meta saving.
- `includes/validation.php`: Registration validation and OTP checks.
- `includes/frontend-scripts.php`: Inline JS for OTP sending and UI tweaks.
- `includes/ajax-otp.php`: AJAX handler for sending OTP.
- `includes/password-policy.php`: Password strength relaxation.

## Notes
- OTP resend is limited to once per 60 seconds per phone.
- OTP expiry is `NARDONE_OTP_EXPIRY` (default 3 minutes).
- Adjust messages via translation functions (`__`, `esc_html__`) if needed.

## Changelog
- 0.3.1: Initial modularized release with OTP registration flow, IPPanel integration, and English comments.

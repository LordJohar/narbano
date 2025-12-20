# Nardone Registration Plugin - AI Coding Instructions

## Project Overview
**Nardone** is a WordPress plugin enabling WooCommerce registration via mobile number + OTP verification through the **IPPanel SMS gateway**. Version: 0.5.0. The plugin is **modular** with clear concerns separation across `includes/` files. All Persian/Arabic digit handling is normalized to Latin numerals throughout.

## Architecture & Data Flow

### OTP Registration Flow
1. **Form rendering** ([registration-form.php](includes/registration-form.php)): Injects fields (first/last name, phone, OTP code input) and hides email via CSS
2. **Client-side** ([frontend-scripts.php](includes/frontend-scripts.php)): Inline jQuery on OTP "Send" button → normalizes digits → triggers AJAX
3. **Backend OTP** ([ajax-otp.php](includes/ajax-otp.php)): Validates phone, checks rate limit (60s), generates 6-digit OTP, stores in transient, calls IPPanel API
4. **Form validation** ([validation.php](includes/validation.php)): On submit, validates phone format/uniqueness, OTP code, checks transient expiry
5. **Customer setup** ([customer-data.php](includes/customer-data.php)): Auto-generates username (`nardoneXXXX`), injects synthetic email, saves phone to user meta

### Transient-Based OTP Storage
- **Key**: `nardone_otp_<md5_of_phone>` (stores `code`, `phone`, `expires`, `last_sent` timestamps)
- **Expiry**: 3 minutes default (`NARDONE_OTP_EXPIRY = 3 * MINUTE_IN_SECONDS`)
- **Rate limit**: Blocks resend within 60s per phone number (checked via transient timestamps)
- **Lookup**: Query `get_users( array('meta_key' => 'billing_phone', 'meta_value' => $phone) )` for phone uniqueness

### Critical Helpers
- **`nardone_normalize_phone_digits()`**: Converts Persian (۰۱۲۳۴۵۶۷۸۹) & Arabic (٠١٢٣٤٥٦٧٨٩) to Latin (0-9), strips whitespace
- **`nardone_generate_username()`**: Loops `do { $u = 'nardone' . wp_rand(1000,9999) } while (username_exists($u))`
- **Phone validation**: Always use normalized input against regex `^09[0-9]{9}$`

## Project-Specific Conventions

### Digit Normalization (CRITICAL)
**Every** phone input must pass through `nardone_normalize_phone_digits()` before regex checks or storage:
```php
$phone = nardone_normalize_phone_digits( $_POST['billing_phone'] );
if ( ! preg_match( '/^09[0-9]{9}$/', $phone ) ) { /* error */ }
```

### Synthetic Email (Required for WooCommerce)
If registration email is empty, generate fallback before saving user:
- Format: `u<phone_digits>+<rand4>@noemail.nardone` (e.g., `u9121234567+5678@noemail.nardone`)
- Set in two places: (1) [customer-data.php](includes/customer-data.php) via `woocommerce_new_customer_data` filter, (2) early `init` hook injection into `$_POST['email']`
- Phone is stored as user meta `billing_phone` for future lookups

### IPPanel SMS API
**Endpoint**: `https://edge.ippanel.com/v1/api/send` | **Auth**: Bearer token in `Authorization` header  
**Request body** (from [ajax-otp.php](includes/ajax-otp.php) line ~70):
```json
{
  "sending_type": "pattern",
  "from_number": "<option: nardone_otp_from_number>",
  "code": "<option: nardone_otp_pattern_code>",
  "recipients": ["989121234567"],
  "params": { "otp_code": "123456" }
}
```
Validation & rate-limit checks happen **before** this call; failures return `wp_send_json_error()`.

### WordPress Hooks & Security
- **AJAX nonce**: Verify `wp_verify_nonce( $_POST['nonce'], 'nardone_send_otp' )` in [ajax-otp.php](includes/ajax-otp.php)
- **Input sanitization**: `sanitize_text_field( wp_unslash( $_POST['field'] ) )`
- **AJAX actions**: `wp_ajax_nardone_send_otp` and `wp_ajax_nopriv_nardone_send_otp` (both logged-in and guests)
- **Validation hook**: `woocommerce_registration_errors` to add/remove errors; remove `'registration-error-email-required'` & `'registration-error-invalid-email'`
- **Data hooks**: `woocommerce_new_customer_data` (pre-insert) and `woocommerce_new_customer` (post-insert)

### Option Keys (Always Use Constants)
Reference settings via `NARDONE_OPT_*` constants from [constants.php](includes/constants.php):
```php
NARDONE_OPT_API_KEY       // 'nardone_otp_api_key'
NARDONE_OPT_PATTERN_CODE  // 'nardone_otp_pattern_code'
NARDONE_OPT_FROM_NUMBER   // 'nardone_otp_from_number'
NARDONE_OPT_PATTERN_PARAM // 'nardone_otp_pattern_param' (default: 'otp_code')
```
Settings page: **Settings → Nardone OTP** (registered via [admin-settings.php](includes/admin-settings.php))

### Translation Strings
All user-facing text uses text domain `'nardone'`:
```php
__( 'Please enter your mobile number.', 'nardone' )
esc_html__( 'Verification code (OTP)', 'nardone' )
```

## Common Tasks

**Add a registration field**:
1. Inject via `woocommerce_register_form` hook in [registration-form.php](includes/registration-form.php)
2. Add validation in [validation.php](includes/validation.php) (after phone validation)
3. Save via `woocommerce_new_customer_data` filter in [customer-data.php](includes/customer-data.php)

**Change OTP SMS template**:
1. Edit `"params"` array in [ajax-otp.php](includes/ajax-otp.php) around line 70
2. Ensure key name matches `get_option( NARDONE_OPT_PATTERN_PARAM )` value

**Debug OTP transient**:
```php
$phone = '989121234567'; // Normalized
$otp_key = 'nardone_otp_' . md5( $phone );
$data = get_transient( $otp_key ); // Check if exists & not expired
```

## Testing & Verification
- **Digit normalization**: Test Persian (۰۹۱۲۱۲۳۴۵۶۷) and Arabic (٠٩١٢١٢٣٤٥٦٧) numerals both normalize to Latin
- **OTP expiry**: Request OTP, wait 3+ minutes, submit form → should fail with "expired" error
- **Rate limit**: Send OTP twice within 60 seconds → second should fail with "wait 1 minute" message
- **Phone uniqueness**: Register user A with `09121234567`, try user B with same phone → should fail
- **Synthetic email**: Register without email field → user record should have `u9121234567+XXXX@noemail.nardone`

# Nardone Registration Plugin - AI Coding Instructions

## Project Overview
Nardone is a **WordPress plugin** for WooCommerce that implements mobile-first user registration with OTP verification via **IPPanel SMS gateway**. The plugin operates as a modular system with clear separation of concerns across include files.

## Architecture & Data Flow

### Core Pattern: Transient-Based OTP Storage
- OTP is **generated server-side**, stored in WordPress transients with 3-minute expiry (`NARDONE_OTP_EXPIRY`)
- Transient key format: `nardone_otp_<normalized_phone>` (phone digits normalized to Latin numerals)
- Rate limit: one OTP request per 60 seconds per phone (transient: `nardone_otp_rate_limit_<phone>`)

### Registration Flow (Key Files)
1. **[registration-form.php](includes/registration-form.php)**: Injects form fields (first name, last name, phone) and hides email field
2. **[frontend-scripts.php](includes/frontend-scripts.php)**: Inline JS triggers AJAX OTP request on blur/change
3. **[ajax-otp.php](includes/ajax-otp.php)**: Handles OTP generation → IPPanel API call (Bearer token auth)
4. **[validation.php](includes/validation.php)**: Validates phone format, uniqueness, OTP correctness/expiry on form submit
5. **[customer-data.php](includes/customer-data.php)**: Injects synthetic email (if missing) and saves phone to user meta

### Critical Components
- **[helpers.php](includes/helpers.php)**: 
  - `nardone_normalize_phone_digits()` - Converts Persian/Arabic digits (۰-۹, ٠-٩) to Latin (0-9)
  - `nardone_generate_username()` - Creates unique `nardoneXXXX` usernames
- **[constants.php](includes/constants.php)**: All option keys prefixed `nardone_otp_` (API key, pattern code, sender number, pattern param)
- **[admin-settings.php](includes/admin-settings.php)**: Settings page at Settings → Nardone OTP

## Project-Specific Conventions

### Digit Normalization (Always Required)
Iranian users input Persian/Arabic numerals. **Always normalize phone input through `nardone_normalize_phone_digits()`** before regex validation or storage. Pattern: `^09[0-9]{9}$` (after normalization).

### WordPress Hooks & Security
- Use `wp_verify_nonce()` for AJAX requests (nonce key: `nardone_send_otp`)
- Sanitize input: `sanitize_text_field( wp_unslash( $_POST['...'] ) )`
- Use `wp_send_json_error()` / `wp_send_json_success()` for AJAX responses
- Filter WooCommerce validation errors via `woocommerce_registration_errors` hook

### Option Keys (Use Constants)
Always reference settings through `NARDONE_OPT_*` constants defined in [constants.php](includes/constants.php):
- `NARDONE_OPT_API_KEY` → `nardone_otp_api_key`
- `NARDONE_OPT_PATTERN_CODE` → `nardone_otp_pattern_code`
- `NARDONE_OPT_FROM_NUMBER` → `nardone_otp_from_number`
- `NARDONE_OPT_PATTERN_PARAM` → `nardone_otp_pattern_param` (default: `otp_code`)

### Synthetic Email Fallback
WooCommerce requires email. If user provides none, generate: `nardone+<phone>@nardone.local`. Store phone in user meta `billing_phone` for lookup.

### Translation Strings
Use `__()` and `esc_html__()` with text domain `'nardone'` for all user-facing strings. Example: `__( 'Invalid phone format.', 'nardone' )`.

## External Integration: IPPanel SMS API

**Endpoint**: `https://edge.ippanel.com/v1/api/send` (POST)  
**Auth**: Bearer token (API key in Authorization header)  
**Payload**: 
```json
{
  "recipient": "989123456789",
  "templateName": "<pattern_code>",
  "parameters": {
    "<pattern_param_name>": "<random_otp_code>"
  }
}
```

Rate limiting and validation occur **before** the API call in [ajax-otp.php](includes/ajax-otp.php).

## Common Tasks & Patterns

**Add new registration field**: 
- Add to [registration-form.php](includes/registration-form.php) via `woocommerce_register_form` hook
- Validate in [validation.php](includes/validation.php) before OTP checks
- Store in [customer-data.php](includes/customer-data.php) via `woocommerce_new_customer` hook

**Modify OTP message template**: 
- Change `"parameters"` structure in [ajax-otp.php](includes/ajax-otp.php) line ~70
- Ensure pattern variable name matches `NARDONE_OPT_PATTERN_PARAM` setting

**Add admin notification**: 
- Use `admin_notices` hook in [dependencies.php](includes/dependencies.php) pattern
- Trigger on `plugins_loaded` action to ensure WooCommerce is loaded

## Testing Checklist
- Phone format validation: Latin & Persian/Arabic digits both work
- OTP expiry: Request after 3+ minutes should fail
- Rate limit: Two requests within 60 seconds should fail
- Synthetic email: Missing email falls back to `nardone+phone@nardone.local`
- IPPanel API: Check for Bearer token in request headers

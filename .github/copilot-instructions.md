# Nardone Registration Plugin — AI Coding Guide

Concise, codebase-specific rules to get productive fast. This is a WordPress + WooCommerce plugin that adds OTP-by-SMS registration/login via IPPanel. No build tooling; work directly in PHP templates/hooks.

## Architecture map (key files)
- `nardone-registration.php`: bootstrap; loads modules in order: `core-functions.php`, `registration-handler.php`, `login-handler.php`, `checkout-*`, `admin-settings.php`, `frontend-assets.php`, `ajax-otp.php`.
- `includes/constants.php`: option keys/constants (use `NARDONE_OPT_*`, `NARDONE_OTP_EXPIRY`).
- `includes/helpers.php`: **digit normalization**, username generator, name masking. Always reuse these.
- `includes/dependencies.php`: WooCommerce presence guard.
- `includes/registration-form.php`: renders My Account form fields (first/last, phone, OTP) and hides email.
- `includes/frontend-scripts.php`: inline jQuery for OTP send + referrer blur lookup (normalizes digits before AJAX).
- `includes/ajax-otp.php`: AJAX send OTP + referrer lookup; validates nonce, rate-limit (60s), expiry (3m), IPPanel call.
- `includes/validation.php`: server-side registration validation (phone format/uniqueness, OTP correctness/expiry, soft referrer warn).
- `includes/customer-data.php`: injects synthetic email + username, saves phone + referrer meta.
- `includes/login-handler.php`: phone-based login helper; `checkout-*.php` trio adds inline checkout OTP login.
- `includes/password-policy.php`: relaxes WC password rules (meter visible, not enforced).
- `templates/myaccount/form-register.php`: WC template override used by registration form.

## Non-negotiable conventions
- **Digit normalization everywhere**: call `nardone_normalize_phone_digits()` before regex/storage. Accepts Persian/Arabic digits and +98/98/0098 → `09XXXXXXXXX`. Validate with `'^09[0-9]{9}$'`.
- **OTP transient**: key `nardone_otp_<md5(phone)>` storing `code, phone, expires, last_sent`; expiry = `NARDONE_OTP_EXPIRY` (3m). Reject resend within 60s.
- **Synthetic email**: if missing, set `u<phone>-<rand4>@noemail.nardone` both in early POST shim and `woocommerce_new_customer_data` (see `customer-data.php`). Phone saved as `billing_phone` user meta; uniqueness via `get_users` on that meta.
- **Username**: generated `nardone####` via helper loop until unique.
- **Referrer**: optional phone stored as `nardone_referrer_phone`; when matched, also store `nardone_referrer_user` and masked name via helper. Validation is soft (warn, don’t block).
- **i18n**: text domain `nardone` for all strings.

## IPPanel integration
- Endpoint `https://edge.ippanel.com/v1/api/send`, bearer token header. Pattern payload uses `from_number`, `code`, `recipients` (MSISDN `98...`), `params` keyed by option `NARDONE_OPT_PATTERN_PARAM` (default `otp_code`). See `ajax-otp.php` around body build.

## Hooks & security
- AJAX: `wp_ajax_nardone_send_otp` / `wp_ajax_nopriv_nardone_send_otp`; nonce `nardone_send_otp`. Checkout login analogs use `nardone_checkout_login` nonce.
- Registration validation hook: `woocommerce_registration_errors` also removes default email-required/invalid errors.
- Data hooks: `woocommerce_new_customer_data` + `woocommerce_new_customer` for meta persistence.
- Sanitize inputs: `sanitize_text_field( wp_unslash( ... ) )` before use.

## Checkout inline OTP login
- `checkout-redirect.php`: guest redirect flow.
- `checkout-login.php`: renders sidebar OTP login form.
- `checkout-handler.php`: AJAX login flow (nonce check, OTP transient read/validate, login user).

## Common tasks (patterns)
- Add a field: render in `registration-form.php`; validate in `validation.php` after phone; persist via `woocommerce_new_customer_data` in `customer-data.php`.
- Change SMS template param name: update option or JSON `params` in `ajax-otp.php` (keep key aligned with `NARDONE_OPT_PATTERN_PARAM`).
- Debug OTP: compute key `nardone_otp_<md5(normalized_phone)>`, inspect transient; ensure resend ≥60s.

## Working notes
- No build/test commands; test in a WP+WC site. Ensure WooCommerce active (`dependencies.php`).
- Keep module order intact in bootstrap; avoid adding `init` hooks there—add actions/filters inside respective include files.

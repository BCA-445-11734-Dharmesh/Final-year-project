# Project Improvements & Action Plan

This document lists recommended security, payment, UX, and developer improvements for this project, prioritized and actionable. Use it as a roadmap for incremental fixes and enhancements.

## Priority A — Critical (Fix immediately)
- SQL injection: convert all raw `mysqli_query()` calls to prepared statements.
- CSRF protection: add a session-based CSRF token generator and validate tokens on all state-changing POST forms.
- Payment integrity: store and verify payment provider order IDs and amounts before marking orders paid.
- Session security: call `session_regenerate_id(true)` after login and set session cookie flags (`Secure`, `HttpOnly`, `SameSite`).

## Priority B — High
- Input validation & XSS: apply `htmlspecialchars()` to all user-controlled output and validate inputs server-side.
- Rate limiting & account lockout: add failed-login counters, lockouts, and throttle payment callback endpoints.
- Secrets management: move API keys from `config.php` into environment variables (use `.env`) and ignore secrets in git.
- Security headers & HTTPS: add HSTS, CSP, X-Frame-Options, X-Content-Type-Options and force HTTPS in entry scripts.

## Priority C — Medium
- Logging & audit: add `audit_log` table and write logs for admin actions, payment callbacks, and failed logins.
- Email notifications: send receipts/confirmations for successful payments and important account changes.
- Error handling: stop showing raw DB/errors to users; log internally and display generic messages.
- Password policy: enforce stronger passwords (8+ chars, mix of char types) and show a strength meter.

## Priority D — UX, Accessibility & Features
- Improve mobile responsiveness and add ARIA attributes for core pages.
- Add clearer payment-status pages and retry options when payments fail.
- Add a privacy / data-retention policy and user-facing account deletion flow.

## Priority E — Developer Experience & Operations
- Add `.env` support and update `config.php` to read from environment.
- Add a `README.md` setup section with steps to configure Razorpay keys, database, and SSL.
- Add basic unit/integration tests for auth and payment flows; add CI pipeline (GitHub Actions).
- Add database migration scripts or a `fix_*.php` folder conversion to proper migrations.

## Suggested Quick Wins (copy-paste snippets)

- CSRF token generator (session):

```php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// In forms: <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
// On submit: if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
```

- Prepared statement example:

```php
$stmt = $conn->prepare('SELECT id, name FROM members WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
```

- Set secure cookie flags and regenerate session:

```php
session_start();
session_regenerate_id(true);
session_set_cookie_params([ 'lifetime' => 0, 'path' => '/', 'secure' => isset($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Lax' ]);
```

## Next steps (recommended order)
1. Add `IMPROVEMENTS.md` (this file).
2. Implement prepared statements for the top 5 risky files: `chat.php`, `payments.php`, `payments_*` callbacks, `members` edits.
3. Add CSRF protection across all forms.
4. Harden sessions & cookies, add security headers.
5. Move secrets into `.env` and update `config.php`.
6. Add logging/audit and email receipts.

---
If you want, I can start implementing the top critical fixes now (prepared statements + CSRF + session hardening). Reply with `implement` and I'll begin patching the highest-risk files one-by-one and update the todo list accordingly.

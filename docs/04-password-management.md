# Password Management

Outline - fill in once `PasswordService` is built.

- Forgot/reset flow: `forgot()` deliberately returns the same success message whether or not the identifier matches a real user (no user-enumeration leak) - only a real match creates a `Reset` row.
- Two-step verification: `verify($token)` alone checks the token; `verify($token, $code)` also checks the code, incrementing an attempt counter on mismatch.
- Locked accounts can still reset their password - `verify()` returns a distinct "locked" message rather than an error, since resetting is how a locked user might get unstuck.
- `save()` requires the code (body) and token (URL) to match the same `Reset` row, runs `canUsePassword()` (recent-password reuse block, `cuztomisable.account.passwords.reuse_after`), and only emails a reset-notification if `cuztomisable.notifications.reset` is truthy.
- Authenticated self-service `change()` vs admin-forced (`force=true`, only allowed when `change_password` is already flagged) vs admin-issued temporary password (`send()`, sets `change_password=true` for the next forced change).
- `invalidate_sessions` on `change()` - revokes every other Sanctum token except the current request's own.
- The `lock()` email-link endpoint: redirect-based (not JSON), only works within a week of the reset request, notifies `CUZTOMISABLE_ADMIN` when it actually locks the account.

## See also

- [Authentication](01-authentication.md)
- [Users](05-users.md)

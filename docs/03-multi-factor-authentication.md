# Multi-Factor Authentication

Outline - fill in once `MfaService`/`OtpService` are built (Steps 7 and 10 of the refactor plan).

- When MFA actually triggers: driven by `IpAddress::requireMfa()`, not a blanket per-user setting - a "new" or non-remembered IP forces it even for a user who otherwise has MFA off.
- Delivery channels: email and/or phone, per `cuztomisable.login.multi_factor_authentication.send_via` - and how `type=resend` falls back through `code->sent_via` then config's phone-then-email preference.
- Code lifecycle: send → resend (cooldown-gated) → verify (read-only status check) → save (actually consumes it).
- Attempt-counting and exhaustion: wrong code increments `attempt_counter`; hitting the max **deletes the code row entirely**, not just marks it failed.
- `remember` on save: marks the `IpAddress` as remembered for `session_length` (or 60 days if unset), skipping MFA on future logins from that IP until it expires.
- How this relates to the planned TOTP addition (Step 10) - email/phone codes stay as-is, TOTP becomes a third method under the new `OtpMethod` enum, not a replacement.

## See also

- [Authentication](01-authentication.md)
- [Configuration](10-configuration.md)

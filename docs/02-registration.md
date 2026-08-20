# Registration & Invitations

Outline - fill in once `RegistrationService` is built.

- Self-registration vs admin-issued invites - same underlying `user_registrations` table, two entry points (`save()` with no code vs `invite()` + `save()` with a code).
- Why `save()` collapses every bad-code case (unknown/expired/used) into one generic "not found" response, while `verify()` differentiates them (403 expired / 401 used / 404 deleted) - a deliberate asymmetry, not an oversight, worth documenting so it isn't "fixed" by accident later.
- Attempt-counting on `verify()` and automatic soft-delete once `cuztomisable.account.registration.attempts.max` is hit.
- Resend cooldown (`send()`) and its `resend_after` config.
- Who gets notified: the invitee always (registration/invitation email), the inviter/admin only if `cuztomisable.account.registration.send_notification` is true.
- The `email_verification` config gotcha: it holds an array, so the `!== false` check gating the verification email is always true today regardless of whether verification is meaningfully "on" - real current behavior, flag it here rather than let it be a silent surprise.

## See also

- [Authentication](01-authentication.md)
- [Users](05-users.md)

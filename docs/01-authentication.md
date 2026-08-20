# Authentication

Outline - fill in once `Authentication\LoginController`/`LoginService` are migrated (Step 7 of the refactor plan).

- Login flow: username/email/phone identifier (`IdentifierType`), credential check, `canLogIn()` gate (locked accounts, verification requirements).
- Web session vs mobile: signed auth cookie (`generateAuthCookie()`) vs `X-App-Platform: mobile` returning `access_token`/`refresh_token` + a `Models\Personal\RefreshToken` row.
- What happens when the login IP requires MFA instead of logging in directly.
- Failed-login attempt counting (`addAttempt()`) and the resulting lockout/timer behavior, config-driven via `cuztomisable.login.attempts`.
- Logout: token revocation + cookie clearing.
- Mobile token refresh (`/api/refresh/token`) as a distinct, public, credential-is-the-token flow - separate from web `/api/refresh`.

## See also

- [Registration](02-registration.md)
- [Multi-Factor Authentication](03-multi-factor-authentication.md)
- [Configuration](10-configuration.md)

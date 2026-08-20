# Security

Outline - fill in as the relevant pieces get built/verified.

- Rate limiting: nearly every mutating endpoint calls `Controller::rateLimit()` with a per-action, per-identifier cache key (IP + user/target id) - document the actual key patterns and default limits (`cuztomisable.rate_limits`) once they're consolidated.
- User enumeration is deliberately avoided on `PasswordController::forgot()` (same success response regardless of match) - confirm the same discipline holds wherever else an identifier lookup could leak existence.
- Password history / reuse prevention (`canUsePassword()`, `cuztomisable.account.passwords.reuse_after`).
- Session/token model: web (signed cookie via Sanctum) vs mobile (bearer access token + separate hashed refresh token, `Models\Personal\RefreshToken`) - two different credential lifetimes and revocation paths.
- MFA/OTP as a second factor - see [Multi-Factor Authentication](03-multi-factor-authentication.md) - and the planned TOTP addition.
- Once multi-tenancy lands (Step 9): document the single-org (`HasOrganization`/`OrganizationScope`) vs N-org (`BelongsToOrganizations`) split and what each actually protects against.
- Encrypted-at-rest fields once they exist (TOTP secrets, social account tokens - Steps 10-11) - mirror Blocksmith's `10-security.md` treatment of encrypted signing keys as the pattern to follow.

## See also

- [Authentication](01-authentication.md)
- [Configuration](10-configuration.md)

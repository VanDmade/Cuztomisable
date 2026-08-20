# IP Addresses & Trusted Devices

Outline - fill in once `IpAddressService` is built.

- Every login records/updates an `IpAddress` row (`last_used_at`) - this is also what `requireMfa()` (see [Multi-Factor Authentication](03-multi-factor-authentication.md)) checks against.
- Scoping: `ipQuery()` returns only the caller's own IPs unless `Auth::user()->admin` is true, in which case it's every IP system-wide (optionally filtered by `user_id`) - test both sides, not just self.
- `forget()` clears a remembered/trusted IP so it requires MFA again next time; only meaningful on an IP that's actually remembered (`remember=true`), otherwise it's a no-op 202.
- `toggleDelete()` - same soft-delete-toggle shape as Roles/Permissions/Registration.
- `save()` only updates the `label` - nothing else about an IP record is user-editable through this endpoint.
- **Known portability issue:** `table()`'s query uses a raw MySQL `IF(...)` expression for the computed `remember` column - won't run as-is against the SQLite test database. Needs resolving (a portable `CASE WHEN` or an accessor) when this gets migrated, not silently worked around in the test.

## See also

- [Users](05-users.md)
- [Authentication](01-authentication.md)

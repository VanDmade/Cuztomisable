# Artisan Commands

Outline - fill in once Step 13 (Jobs/Events/Middleware/Console/Facade) lands.

- `AddOrganizationScopingCommand` - adds a nullable `organization_id` column to every table configured under `cuztomisable.tables`, mirroring Blocksmith/Hookamatic's version of the same command (same closure-`use($column)` bug already found and fixed in those two packages - confirm it doesn't recur here when this gets written).
- Any org/terms/OTP maintenance commands that fall out of the later feature steps (multi-tenancy, terms & conditions, TOTP) - listed here as they're actually built, not speculated in advance.
- `CuztomisableCommand` base class behavior - console vs non-console failure handling (falls back to `CuztomisableLog` when run outside a real console context), mirroring `HookamaticCommand`.

## See also

- [Configuration](10-configuration.md)

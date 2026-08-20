# Configuration

See the [README](../README.md#configuration) for the full file/key reference table - this doc is for the gotchas and the planned consolidation, not a duplicate listing.

## Known issues in the current (pre-refactor) config

- **`config/mobile.php` is never merged.** `CuztomisableServiceProvider::register()` merges nine config files but not this one - `config('cuztomisable.mobile.*')` always falls back to hardcoded defaults today, regardless of what `mobile.php` actually contains. Confirmed live at `UserController::refreshToken()`. Fix target: Step 3 of the refactor plan, folded into the ten-files-to-one consolidation.
- **`config('cuztomisable.notifications.email_verification', false) !== false`** (and the equivalent `reset`/`registered` checks) are always true today, because the config value is an array, and an array is never `===` to the boolean `false` regardless of content. Not a bug exactly - just means "verification email sending" isn't actually toggleable via that key the way the name implies.
- **`Helpers/Respondify.php` reads `config('respondify.errors.*')`** (bare `respondify` namespace) but the app only ever merges these files under `cuztomisable.respondify` - so DB error-logging via Respondify is permanently off unless a host app separately publishes a bare `respondify.php`. Worth deciding whether this gets fixed when `Respondify` becomes `Support\ResponseService` (Step 5), or left as-is.

## Planned: ten files → one

Step 3 of the refactor plan consolidates `app.php`/`login.php`/`account.php`/`mobile.php`/`notifications.php`/`locations.php`/`images.php`/`rate_limits.php`/`respondify.php`/`tablelify.php` into a single `config/cuztomisable.php`, fixing the `mobile.php` merge bug on the way. Document the final key shape here once that phase actually lands - don't pre-write it, the shape may shift during consolidation.

## See also

- [README - Configuration](../README.md#configuration)

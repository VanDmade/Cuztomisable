# Configuration

See the [README](../README.md#configuration) for the full file/key reference table - this doc is for the gotchas and the planned consolidation, not a duplicate listing.

## Current shape

The old ten-files-to-one consolidation (Step 3) is done: `config/cuztomisable.php` is the single
file a host app publishes and edits (`php artisan vendor:publish --tag=cuztomisable-config`). The
`mobile.php` merge bug is fixed - `cuztomisable.mobile.*` resolves normally.

`config/email.php` and `config/text.php` are a deliberate exception, added when email/text
settings were split out of the `notifications` array for readability - they're merged into
`cuztomisable.notifications.emails`/`.texts` by the service provider and are never published
separately; see [Emailing and Texting](12-emailing-and-texting.md#config).

The email/phone verification "always true" truthy-array bug (`!== false` against an array that's
never `=== false`) is also fixed - `email_verification`/`phone_verification`/`reset` now use a
real `enabled` boolean.

## Known issue

- **`Helpers/Respondify.php` reads `config('respondify.errors.*')`** (bare `respondify`
  namespace) but the app only ever merges config under `cuztomisable.respondify` - so DB
  error-logging via Respondify is permanently off unless a host app separately publishes a bare
  `respondify.php`. The response-shaping logic itself already moved into
  `CuztomisableController` (`success()`/`error()`/`debug()`), but the config section is still
  named `respondify` - worth renaming (`response`/`errors`) while this gets fixed.

## See also

- [README - Configuration](../README.md#configuration)
- [Emailing and Texting](12-emailing-and-texting.md)

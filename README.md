# Cuztomisable

A Laravel package providing a complete auth + portal foundation: user management, roles/permissions, MFA, password reset, registration/invites, multi-tenant organizations, terms & conditions acceptance, social login, and a Vue 3 (Inertia) frontend — all installed into a host app via a single Artisan command.

Cuztomisable ties into the host app, not the other way around: the host requires this package and publishes what it needs, but nothing in here depends on host-specific code.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13 (`illuminate/support ^11.3|^12.0|^13.0`)
- Laravel Sanctum
- Laravel Socialite (for social login)

## Installation

Add the local path repository to your host app's `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/VanDmade/Cuztomisable"
    }
]
```

Require it and run the installer:

```bash
composer require vandmade/cuztomisable:@dev
php artisan cuztomisable:install
```

`cuztomisable:install` does everything needed to get running:

1. Publishes config (`cuztomisable-config`), migrations (`cuztomisable-migrations`), and framework/page assets (`cuztomisable-framework`, `cuztomisable-pages`)
2. Generates a `sessions` table migration if the host doesn't already have one
3. Publishes Sanctum's `personal_access_tokens` migration if missing (the shipped `User` model uses `HasApiTokens`)
4. Copies `resources/sass/variables.example.scss` → `resources/sass/variables.scss` if it doesn't exist yet, so first-time styling has sane defaults without overwriting anything you've customized
5. Runs `migrate --force`
6. Seeds roles, permissions, role/permission links, a default admin user, and default settings
7. Prints the seeded admin login (`CUZTOMISABLE_ADMIN` env var, default `admin@cuztomisable.com` / `password`) — change it on first login

### Publish tags

Only `cuztomisable-config` publishes something into the host's `config/` directory (`config/cuztomisable.php`) — the rest of this package's config files (`email.php`, `text.php`, `passwords.php`, `rate_limits.php`, `social.php`) are merged into `cuztomisable.*` at boot and aren't meant to be published; override their values from your own `config/cuztomisable.php` instead, or with env vars where the file already reads one.

| Tag | Publishes |
|---|---|
| `cuztomisable` | Everything below, in one shot |
| `cuztomisable-config` | `config/cuztomisable.php` |
| `cuztomisable-assets` | `cuztomisable-framework` + `cuztomisable-pages` combined |
| `cuztomisable-framework` | App shell: `resources/js/{bootstrap,cuztomisable,store}.js`, `components/`, `queues/`, `routers/`, `utils/`, `resources/sass/`, `resources/languages/en/` → `lang/en/cuztomisable` |
| `cuztomisable-pages` | The actual screens: `resources/js/views/` and the Inertia root view `resources/views/index.blade.php` |
| `cuztomisable-migrations` | `database/migrations/` → `database/migrations/cuztomisable` |
| `cuztomisable-emails` | `resources/views/emails/` → `resources/views/vendor/cuztomisable` |

Branding images (logo, favicon, etc.) are never published — `BrandingController` serves them live from the package at `GET /cuztomisable/{filename}` with a long-lived cache header, so there's no raw copy in `public/` to fall out of sync.

## Configuration

The published config lives at `config/cuztomisable.php` and covers:

- `sms_provider` — the `SmsProviderInterface` implementation used to send texts (default: `AwsSnsSmsProvider`)
- `resources` — override which API Resource class shapes a given model's JSON output
- `app` — home route, mobile-agent validation rules, sidebar/navbar links
- `login` — identification method (email/phone/username), remember-me, session length, lockout attempts, verification requirements, MFA settings
- `account` — code/token lengths and expiry, default account lock state, default timezone, registration rules, profile address fields, admin temporary-password rules
- `mobile` — refresh-token behavior for mobile clients
- `notifications` — toggles for new-IP alerts, email/phone verification, password-reset confirmation (delivery settings themselves live in `email.php`/`text.php`, merged in as `cuztomisable.notifications.emails`/`.texts`)
- `locations` — default country, countries/states list, country calling codes
- `images` — upload resize width, WebP quality, max encoded size
- `respondify` — JSON error-response formatting and logging
- `tablelify` — pagination defaults for `TableService`
- `settings` — which keys are exposed as admin-editable settings
- `organizations` — multi-tenancy toggle (off by default) and the Organization model class
- `social` — filled in from `config/social.php` at boot; don't edit this key directly
- `terms` — `'enabled' => false` by default; set `true` to require terms & conditions acceptance before a user can use the API (currently shipped as `true` in this repo so the acceptance flow can be tested end-to-end — flip it back to `false` before shipping)

Merge-only config files (not published, edit host-side `config/cuztomisable.php` overrides instead):

| File | Merged into | Configures |
|---|---|---|
| `email.php` | `cuztomisable.notifications.emails` | Email logging on/off, which parameters get redacted from logs, default template branding, default from-address |
| `text.php` | `cuztomisable.notifications.texts` | Text logging on/off, redaction of URLs/codes/whole messages |
| `passwords.php` | `cuztomisable.account.passwords` | Forgot/reset delivery channels, timing between resends, password-reuse rules |
| `rate_limits.php` | `cuztomisable.rate_limits` | Per-action throttle attempts/decay, with a `default` fallback |
| `social.php` | `cuztomisable.social` | Master social-login switch, and per-provider enabled flag, button label, logo flag, and `client_id`/`client_secret`/`redirect` |

## API surface

All routes are registered under `/api` by the service provider, behind a middleware stack (`TokenFromCookie`, `RequireCsrfUnlessMobile`, `EnsureValidMobileAgent`, session/CSRF). If the host has `inertiajs/inertia-laravel` installed, `routes/web.php` is also registered with the matching Inertia pages (`/`, `/login`, `/registration/{code?}`, `/forgot`, `/reset/{token}`, `/mfa/{token}`, `/portal`, `/profile`, `/users`, `/roles`, `/permissions`, `/settings`, etc.).

| Area | Highlights |
|---|---|
| Auth | `POST /login`, `POST /logout` |
| Social login | `GET /auth/{provider}/redirect`, `GET /auth/{provider}/callback` |
| MFA | send/verify/submit code during login, self and admin toggle |
| Password reset | forgot → send code → verify → reset, guest-only |
| Registration/invites | self-registration by code, admin-issued invites (`invite-users`) |
| Terms & conditions | current terms, acceptance status/accept, admin publish/manage (`manage-terms`) |
| Organizations | list, current, switch (`organizations.enabled`) |
| Settings | public read, admin write (`GET/POST /settings`) |
| Roles & permissions | full CRUD (`manage-roles-permissions`) |
| User access | get/set a user's roles+permissions (`view/manage-user-roles-permissions`) |
| Addresses / Phones | per-user CRUD, default flag |
| IP addresses | login history, forget device, soft-delete (`clear-user-logins`) |
| Logs | user activity, email, text (`manage-users`); error log (`view-logs`) |
| Users | full CRUD, lock/unlock, refresh tokens, MFA toggle, verification (`manage-users`/`view-users`) |
| Forms | save/resume in-progress multi-step form state, guest or authenticated |

See `routes/api.php` and `routes/web.php` for the exact route list and permission gates.

## Middleware

Registered as route-middleware aliases by the service provider:

| Alias | Class | Purpose |
|---|---|---|
| `permission` | `CheckPermission` | Abort 403 unless the user has the given permission(s) |
| `require-admin` | `RequireAdmin` | Abort 403 unless the user is an admin |
| `require-current-terms` | `RequireCurrentTerms` | Abort 403 until the user accepts the current terms (no-op unless `cuztomisable.terms.enabled`) |
| `throttler` | `Throttler` | Configurable per-action rate limiting, driven by `config/rate_limits.php` |

Two more run automatically for every API request rather than being applied per-route: `TokenFromCookie` (promotes a cookie-stored token into an `Authorization: Bearer` header) and `RequireCsrfUnlessMobile` (enforces CSRF for browsers, skips it for a verified mobile app via `EnsureValidMobileAgent`).

## Models

All under `VanDmade\Cuztomisable\Models`: `Users\User` (the default concrete user), `Roles\Role` / `Permission` and their pivots, `Organizations\Organization` / `Organizations\User`, `Users\Code` (MFA code), `Users\Passwords\Reset` and `Users\Passwords\Password` (reuse history), `Users\Registration` (invite), `Users\IpAddress`, `Address`, `Phone`, `Image`, `Form` (saved wizard state), `Setting`, `Social\SocialAccount`, `Terms\TermsAndConditions` / `Terms\Acceptance`, `Logs\{User,Email,Text,Error}`, `Personal\{AccessToken,RefreshToken}`.

## Traits (`src/Concerns`)

Compose these into a host app's own User model instead of using `Models\Users\User` directly, if you need a custom user model:

- `CuztomisableUser` — login/lockout rules, permission/role resolution, the relationships a Cuztomisable-managed user needs
- `BelongsToOrganizations` — many-to-many organization membership plus a "current organization" pointer
- `HasOrganization` — auto-scopes a single-organization-owned model (e.g. `Role`) to the current organization
- `Auditable` — tracks `created_by`
- `SoftDeletes` — soft-delete plus `deleted_by` tracking
- `NullsToEmpty` — normalizes `null`/`'null'` request values to `''` before validation (used by the base `CuztomisableRequest::validationData()`, since a `FormData` submission can't send a real `null` for an empty field)
- `Validators\Phone` — shared phone-number validation rules for FormRequests

## Frontend

A Vue 3 SPA wired through **Inertia.js**, not vue-router — `resources/js/cuztomisable.js` is the real entry point: it resolves pages from `resources/js/views/**/*.vue`, wraps them in a login or portal layout automatically, and implements Inertia-native navigation (`$route`/`$router`/`RouterLinkCompat`) instead of a client router. It also registers the shared `cz-*` component library (table, forms, inputs, modals, uploads, etc.), wires up the Vuex store, and handles auth-check/timezone-sync/guest-redirect on boot.

## What's left to build

- **OTP/TOTP authenticator support** — not built. A prior scaffold (empty model/services and a migration) was removed; this will be added back as a real feature later.
- **`resources/js/routers/cuztomisable.js`** is dead code — a vue-router route table left over from before the switch to Inertia-native navigation. Nothing imports it. Safe to delete once confirmed no host app has come to depend on it being published.
- **Terms & conditions default** — `cuztomisable.terms.enabled` ships as `true` in this repo for testing the acceptance flow; set it back to `false` (the documented default) before shipping to production, unless you actually want it on.

## Testing

The package has its own self-contained test suite (Orchestra Testbench) under `tests/`, run independently of the host app via `composer test` (see `TESTING.md` and `.github/workflows/tests.yml`). The host app additionally has its own integration test suite under `tests/Feature/Cuztomisable/`.

## Author

Michael VanDerwerker — [michaelvanderwerkerllc@gmail.com](mailto:michaelvanderwerkerllc@gmail.com)

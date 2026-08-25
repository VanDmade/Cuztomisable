# Cuztomisable

A customizable Laravel authentication and portal package. Provides a complete foundation for applications with user management, role-based access control, multi-factor authentication, and mobile API support out of the box.

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- Laravel Sanctum

## Installation

Add the local path repository to your `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/vandmade/cuztomisable"
    }
]
```

Then require it:

```bash
composer require vandmade/cuztomisable
```

Publish assets and run migrations:

```bash
php artisan vendor:publish --provider="VanDmade\Cuztomisable\CuztomisableServiceProvider"
php artisan migrate
```

### Publish Tags

| Tag | What it publishes | Safe to `--force` re-publish? |
|---|---|---|
| `cuztomisable` | Everything below | No - see per-tag column |
| `cuztomisable-config` | `config/cuztomisable.php` | Only if you haven't hand-edited it |
| `cuztomisable-assets` | Everything in `cuztomisable-framework` + `cuztomisable-pages` + `cuztomisable-branding` | No - see per-tag column |
| `cuztomisable-framework` | App shell logic: `resources/js/{bootstrap,cuztomisable,store}.js`, `resources/js/{components,queues,routers,utils}`, `resources/sass`, `resources/lang/en/cuztomisable` | **Yes** - re-run with `--force` to pull updates. Your own `resources/sass/variables.scss` is never touched (the package only ships `variables.example.scss`). |
| `cuztomisable-pages` | The actual screens: `resources/js/views` and the Inertia root shell (`resources/views/index.blade.php`) | **No** - publish once, then edit these directly (e.g. `resources/js/views/authentication/Registration.vue`). Re-publishing overwrites your edits. |
| `cuztomisable-branding` | Logos → `public/cuztomisable` | **No** - one-time seed. Replace the files yourself; never republish this tag. |
| `cuztomisable-emails` | Email Blade templates → `resources/views/vendor/cuztomisable` | Only if you haven't customized a given template |
| `cuztomisable-migrations` | Migrations → `database/migrations/cuztomisable/` | N/A (migrations are additive) |
| `cuztomisable-seeders` | Seeders → `database/seeders/` | Only if you haven't hand-edited them |

## Configuration

Config files are published to `config/cuztomisable/` and accessed via the `cuztomisable.*` key.

| File | Config key | Description |
|---|---|---|
| `app.php` | `cuztomisable.app` | Home route, mobile agent validation, navigation |
| `login.php` | `cuztomisable.login` | Login methods, MFA, session length, verification |
| `account.php` | `cuztomisable.account` | Passwords, account locking, registration |
| `mobile.php` | `cuztomisable.mobile` | Mobile token refresh settings |
| `notifications.php` | `cuztomisable.notifications` | Email and SMS notification settings |
| `locations.php` | `cuztomisable.locations` | IP tracking and geo-location |
| `images.php` | `cuztomisable.images` | Image storage settings |
| `rate_limits.php` | `cuztomisable.rate_limits` | Per-endpoint rate limiting |
| `respondify.php` | `cuztomisable.respondify` | JSON response formatting defaults |
| `tablelify.php` | `cuztomisable.tablelify` | Paginated table query defaults |

### Home Route (`app.php`)

```php
'home' => env('APP_HOME', '/portal'),
```

Set `APP_HOME` in your `.env` to control where authenticated users land after login.

### Registration (`account.php`)

Registration can be disabled per platform. The platform is identified by the `X-App-Platform` request header (`mobile` or absent for web).

```php
'registration' => [
    'disabled' => [
        'web'    => false,
        'mobile' => false,
    ],
    'length'            => 6,
    'expires_in'        => 3600,
    'send_notification' => true,
    'resend_after'      => 300,
],
```

### Mobile Agent Validation (`app.php`)

Mobile API requests must send `X-App-Platform: mobile`. Requests are validated against the configured app list and minimum version.

```php
'mobile_agent' => [
    'enabled'        => true,
    'api_key'        => null,          // Optional shared key for CSRF bypass
    'api_key_header' => 'X-App-Key',
    'apps' => [
        ['name' => 'My App', 'min_version' => '1.0.0'],
    ],
    'platforms'   => ['Android', 'iOS', 'Other'],
    'log_invalid' => false,
],
```

Expected User-Agent format: `AppName/v1.2.3 (Android)`

## API Routes

All routes are registered under the `/api/` prefix with session and CSRF middleware applied. Authenticated routes require a valid Sanctum token.

### Authentication (public)

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/login` | Log in |
| POST | `/api/logout` | Log out |
| POST | `/api/login/mfa/{token}` | Submit MFA code |
| GET | `/api/login/mfa/{token}/verify` | Verify MFA token |
| POST | `/api/login/mfa/{token}/send` | Re-send MFA code |
| POST | `/api/password/forgot` | Request password reset |
| POST | `/api/password/forgot/{token}` | Submit new password |
| GET | `/api/password/forgot/{token}/verify/{code?}` | Verify reset code |
| GET | `/api/register/verify/{code}` | Look up a registration invite |
| POST | `/api/register/{code?}` | Register a new user |
| POST | `/api/refresh/token` | Refresh Sanctum token |

### Account (authenticated)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/me` | Get current user |
| GET | `/api/refresh` | Refresh session |
| POST | `/api/profile` | Update own profile |
| PATCH | `/api/mfa` | Toggle own MFA |
| POST | `/api/user/change/password` | Change own password |

### User Management (authenticated, `manage-users` / `view-users`)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/users` | Paginated user table |
| GET | `/api/user/{id}` | Get a user |
| GET | `/api/list/users` | Flat user list |
| POST | `/api/user/{id?}` | Create or update a user |
| DELETE | `/api/user/{id}` | Toggle soft-delete |
| PATCH | `/api/user/{id}/locked` | Toggle account lock |
| PATCH | `/api/user/{id}/mfa` | Toggle user MFA (`toggle-user-mfa`) |
| POST | `/api/user/{id}/send/password` | Send temp password (`reset-user-passwords`) |

### IP / Login History (authenticated)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/user/{id}/ips` | Login history for a user |
| GET | `/api/ip/{id}` | Single IP record |
| PATCH | `/api/ip/{id}` | Update IP record |
| DELETE | `/api/ip/{id}/forget` | Remove a remembered device (`clear-user-logins`) |
| DELETE | `/api/ip/{id}` | Soft-delete IP record (`clear-user-logins`) |

### Invitations (authenticated, `invite-users`)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/invites` | Paginated invites table |
| POST | `/api/invite` | Create an invitation |
| POST | `/api/invite/{id}/send` | Resend an invitation |
| DELETE | `/api/invite/{id}` | Delete an invitation |

### Roles & Permissions (authenticated, `manage-roles-permissions`)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/roles` | Paginated roles table |
| GET | `/api/role/{id}` | Get a role |
| GET | `/api/list/roles` | Flat roles list |
| POST | `/api/role/{id?}` | Create or update a role |
| DELETE | `/api/role/{id}` | Delete a role |
| DELETE | `/api/role/{id}/permission/{permission}` | Remove a permission from a role |
| GET | `/api/permissions` | Paginated permissions table |
| GET | `/api/permission/{id}` | Get a permission |
| GET | `/api/list/permissions` | Flat permissions list |
| GET | `/api/list/role/{id}/permissions` | Permissions for a role |
| POST | `/api/permission/{id?}` | Create or update a permission |
| DELETE | `/api/permission/{id}` | Delete a permission |

### User Access (authenticated)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/user/{id}/access` | Get roles/permissions for a user (`view-user-roles-permissions`) |
| POST | `/api/user/{id}/access` | Update roles/permissions for a user (`manage-user-roles-permissions`) |

## Middleware

The service provider registers two middleware aliases:

| Alias | Class | Usage |
|---|---|---|
| `permission` | `CheckPermission` | Abort 403 if the user lacks the given permission |
| `require-admin` | `RequireAdmin` | Abort 403 if the user is not an admin |

```php
Route::get('/admin', AdminController::class)
    ->middleware('permission:manage-users');
```

Mobile requests (identified by `X-App-Platform: mobile`) automatically bypass CSRF via `RequireCsrfUnlessMobile` and are validated against the configured app list via `EnsureValidMobileAgent`.

## Models

| Model | Table | Description |
|---|---|---|
| `User` | `users` | Core user with soft deletes, admin flag, MFA, locking |
| `Role` | `roles` | Assignable roles |
| `Permission` | `permissions` | Granular permissions attached to roles |
| `LoginAttempt` | `login_attempts` | Per-user login attempt tracking |
| `PasswordHistory` | `password_histories` | Prevents reuse of recent passwords |
| `UserAddress` | `user_addresses` | Addresses linked to users |
| `UserPhone` | `user_phones` | Phone numbers linked to users |
| `Invitation` | `invitations` | Registration invite tokens |

## Helpers

### Tablelify

Standardized paginated query builder. Supports sorting, searching, filtering, and per-page configuration from request parameters.

```php
use VanDmade\Cuztomisable\Helpers\Tablelify;

return Tablelify::run($query, $parameters, $searchableColumns);
```

The response includes `data`, `total`, `filtered_total`, `total_pages`, `current_page`, and `per_page`.

## Author

Michael VanDerwerker — [michaelvanderwerkerllc@gmail.com](mailto:michaelvanderwerkerllc@gmail.com)

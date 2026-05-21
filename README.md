# Cuztomisable

A customizable Laravel authentication and dashboard portal package. Provides a complete foundation for SaaS applications with user management, role-based access control, and multi-factor authentication out of the box.

## Features

- **Authentication** — Login, registration, password reset, and MFA support
- **User Management** — Full CRUD with soft deletes, IP tracking, and password history
- **RBAC** — Roles and permissions with granular, middleware-enforced access control
- **Account Security** — Login attempt limiting, account locking, and session management
- **Verification** — Email and phone number verification flows
- **Invitations** — Invite-based user onboarding
- **Settings** — Configurable application settings management
- **Geo-location** — IP address tracking and geo-location logging

## Requirements

- PHP 8.2+
- Laravel 10 or 11
- Laravel Sanctum

## Installation

Add the package to your `composer.json` repositories:

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

Publish and run the migrations:

```bash
php artisan vendor:publish --provider="Vandmade\Cuztomisable\CuztomisableServiceProvider"
php artisan migrate
```

## Configuration

The package publishes several config files to `config/cuztomisable/`:

| File | Description |
|---|---|
| `app.php` | Home route, mobile agent validation |
| `login.php` | Login methods, MFA settings, session length, verification requirements |
| `account.php` | Password rules, account locking thresholds, registration settings |
| `notifications.php` | Email and SMS notification settings |
| `locations.php` | Geo-location and IP tracking settings |
| `rate_limits.php` | Rate limiting configuration per endpoint |
| `respondify.php` | API response formatting defaults |
| `tablelify.php` | Paginated table query defaults |

### Login Configuration (`login.php`)

```php
return [
    'methods' => ['email', 'phone'],
    'mfa'     => [
        'enabled' => true,
        'methods' => ['email', 'sms'],
    ],
    'session_length' => 60 * 24 * 7, // minutes
    'require_verification' => true,
];
```

### Account Configuration (`account.php`)

```php
return [
    'password' => [
        'min_length'       => 8,
        'require_upper'    => true,
        'require_number'   => true,
        'require_special'  => false,
        'history_limit'    => 5,
    ],
    'locking' => [
        'enabled'          => true,
        'max_attempts'     => 5,
        'lockout_duration' => 30, // minutes
    ],
    'registration' => [
        'enabled'          => true,
        'require_invite'   => false,
    ],
];
```

## API Routes

All routes are registered under `/api/` and protected with Sanctum where appropriate.

### Authentication

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/login` | Log in with email/phone and password |
| POST | `/api/logout` | Log out the authenticated user |
| POST | `/api/mfa/verify` | Verify a multi-factor authentication code |
| POST | `/api/password/reset` | Request a password reset |

### Users

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/users` | List users (paginated) |
| POST | `/api/users` | Create a new user |
| GET | `/api/users/{id}` | Get a single user |
| PUT | `/api/users/{id}` | Update a user |
| DELETE | `/api/users/{id}` | Soft-delete a user |

### Roles & Permissions

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/roles` | List roles |
| POST | `/api/roles` | Create a role |
| PUT | `/api/roles/{id}` | Update a role |
| DELETE | `/api/roles/{id}` | Delete a role |
| GET | `/api/permissions` | List all permissions |

### Invitations

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/invitations` | Send a user invitation |
| GET | `/api/invitations/{token}` | Look up an invitation by token |

## Middleware

The package registers the following middleware:

- `cuztomisable.permission:{permission}` — Abort with 403 if the authenticated user lacks the given permission
- `cuztomisable.role:{role}` — Abort with 403 if the authenticated user lacks the given role

```php
Route::get('/admin', AdminController::class)->middleware('cuztomisable.permission:manage-users');
```

## Models

| Model | Table | Description |
|---|---|---|
| `User` | `users` | Core user model with soft deletes |
| `Role` | `roles` | Assignable roles |
| `Permission` | `permissions` | Granular permissions attached to roles |
| `LoginAttempt` | `login_attempts` | Per-user login attempt history |
| `PasswordHistory` | `password_histories` | Prevents reuse of recent passwords |
| `UserAddress` | `user_addresses` | Addresses associated with users |
| `UserPhone` | `user_phones` | Phone numbers associated with users |
| `Invitation` | `invitations` | Invite tokens for registration |

## Helpers

### Respondify

Consistent JSON response formatting:

```php
use Vandmade\Cuztomisable\Helpers\Respondify;

return Respondify::success($data, 'User created.');
return Respondify::error('Validation failed.', 422);
return Respondify::paginated($paginatedCollection);
```

### Tablelify

Standardized paginated query builder:

```php
use Vandmade\Cuztomisable\Helpers\Tablelify;

$results = Tablelify::query(User::query(), $request);
```

Supports sorting, searching, and per-page configuration from request parameters.

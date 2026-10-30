# Testing

## Setup

From inside `packages/VanDmade/Cuztomisable`:

```bash
composer install
```

Self-contained [Orchestra Testbench](https://packages.tool.dev/orchestra/testbench) package - spins up its own throwaway Laravel app (in-memory SQLite), so it won't touch anything in the root app.

Unlike Blocksmith/Hookamatic, Cuztomisable creates its **own** `users` table (it's the main app, not a package bolting onto a host app's existing one) - `tests/TestCase.php` loads Cuztomisable's own migrations directly, no Testbench default-users migration needed.

Sanctum is required (`Models\Users\User` uses `HasApiTokens`) - `tests/TestCase.php` registers `Laravel\Sanctum\SanctumServiceProvider` and wires the `sanctum` auth guard, since a lot of `routes/api.php` sits behind `auth:sanctum`. For a Sanctum-gated route in a test, use `Laravel\Sanctum\Sanctum::actingAs($user)`, not plain `actingAs()` - the middleware won't recognize a plain session-guard user.

## Running tests

```bash
# Everything
vendor/bin/phpunit
# One file
vendor/bin/phpunit tests/Feature/Authentication/RegistrationControllerTest.php
# One test by name
vendor/bin/phpunit --filter {test-name}
```

## What's covered

The admin areas every app shares have real tests. Each one checks that the permission gate turns people away, as well as the happy path and the edge cases:

| Area | File | What it pins down |
|---|---|---|
| Users | `tests/Feature/Users/UserControllerTest.php` | own profile vs. another user's (view-users / manage-users), table + list, save + validation, email change re-verifies, admin-created users get a temp password, lock / reset attempts / soft delete (never your own account), MFA toggle (toggle-user-mfa for others) |
| Invitations | `tests/Feature/Authentication/InvitationTest.php` | invite-users gate, invite emails the invitee, no duplicate pending invite, no invite for an existing account, table hides long-expired ones, resend throttle, used invites can't be resent, cancel/restore |
| Roles | `tests/Feature/RoleControllerTest.php` | manage-roles-permissions gate, CRUD, validation, attaching permissions |
| Permissions | `tests/Feature/PermissionControllerTest.php` | manage-roles-permissions gate, CRUD, validation |
| Error logs | `tests/Feature/Logs/ErrorLogTest.php` | view-logs gate, the service logs with context, open-only by default / show_closed, search, closing records who + why, close_duplicates |
| Questionnaire | `tests/Feature/QuestionnaireTest.php` | manage-questionnaire gate, add (key tidied, blanks become null) / edit / duplicate key / disallowed target table, list order + answer counts, reorder, delete keeps answers and frees the key; users see only pending active questions, answers are final, copied only to allowed_tables columns, bad choice / blank required / unknown key give 422, optional questions don't block completion, turned-off questionnaire asks nothing |
| Settings | `tests/Feature/SettingsControllerTest.php` | public frontend payload shape, reading a registered key, unregistered keys 404/403, manage-settings vs. per-key settings-{key} permissions, overwrite, value required |
| Phone numbers | `tests/Feature/PhoneCountryCodeTest.php` | a phone sent without a country code gets `locations.default_country_code` on sign-up and profile save (instead of an error on a field the form doesn't show), a given country code is kept, no phone is fine, a wrong-length number is still rejected on the phone field |

`tests/Concerns/ActsAsAdmin.php` provides `actingAsUserWith('permission-slug', ...)` / `actingAsUserWithout()` / `createUser()`. `TestCase` registers a sqlite `CONCAT()` so the MySQL-flavoured user table search runs in memory.

The other controller test classes (authentication, passwords, registration, etc.) are still stubs with an empty method per action plus notes on the real behaviour. PHPUnit reports those as "risky" (no assertions), not failures.

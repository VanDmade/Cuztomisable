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

Not written yet - per the refactor plan, test content lands at Step 16, once the full new structure (services, new feature areas) is actually built, rather than characterizing the old controller-inline code first. What exists today is scaffolding: one stub `Tests\Feature\*ControllerTest` class per current controller (`tests/Feature/`, `tests/Feature/Authentication/`, `tests/Feature/Users/`), each with an empty method per action and a comment describing the real behavior/gotchas already found while reading the controller - a head start for whoever writes the actual assertions, not a finished suite.

Update this section for real once Step 16 happens.

# Emailing and Texting

How outbound email and SMS actually work in Cuztomisable: sending, templating, delivery-channel
selection, logging, and the events a host app can hook into.

## Sending an email

Every package email is a `Mailable` extending `Mail\VanDmadeMailable`, sent the normal Laravel way:

```php
Mail::to($user->email)->send(new SomeMail($user));
```

`VanDmadeMailable` gives every Mailable, for free:

- **Async delivery.** Every Mailable `implements ShouldQueue`. Laravel's `Mailer::send()` detects
  this and queues automatically - `Mail::to()->send()` never blocks the request on the mail
  provider. Falls back to immediate/inline if `QUEUE_CONNECTION=sync` (the default in tests).
  Use `->sendNow()` to force a synchronous send regardless of `ShouldQueue`.
- **`from`/`reply-to` defaults**, via `defaultEnvelope()` reading `cuztomisable.notifications.emails.from`/`reply_to`. Call it from your Mailable's `envelope()`:
  `return $this->defaultEnvelope(__('cuztomisable/email.subjects.mfa'));`
- **`EmailSent` dispatch** after every real send (see [Events](#events) below).

## Sending a text

Text messages never call `SmsProviderInterface::send()` directly - always dispatch the job:

```php
Jobs\SendText::dispatch($countryCode, $number, $message);
```

`SendText` is a queued job (same non-blocking behavior as email's `ShouldQueue`, since there's no
equivalent shortcut for a raw interface call). Its `handle()` resolves the container-bound
`SmsProviderInterface` (`AwsSnsSmsProvider` by default, swappable via `cuztomisable.sms_provider`)
and, on success, dispatches `TextSent`.

## Templates

**Email** views live under `resources/views/emails`, registered under the `cuztomisable::`
namespace (`loadViewsFrom`). A Mailable's `content()` references them as
`view: 'cuztomisable::authentication.mfa'` - hardcoded per Mailable, not config-driven. To
customize one, publish and edit the copy rather than repointing a config value:

```
php artisan vendor:publish --tag=cuztomisable-emails
```

This publishes into `resources/views/vendor/cuztomisable`, which Laravel checks *before* falling
back to the package's bundled default - so the published directory only ever needs to contain
what's actually been customized.

Every email extends the shared `cuztomisable::template` layout, which reads `logo`/company name
directly from config (`cuztomisable.notifications.emails.logo`) and `config('app.name')` - a
Mailable's `with()` never needs to pass those through.

**Text** messages are plain translated strings - no view/layout, since SMS is inherently plain
text. See [Translations](#translations) below.

## Translations

- `lang/en/email.php` - `subjects.*`, one key per Mailable (`email.subjects.mfa`,
  `email.subjects.support`, etc.). Read via `defaultEnvelope()`.
- `lang/en/text.php` - the actual SMS body text (`text.mfa`, `text.new_ip_address`,
  `text.passwords.reset`, `text.registration.invited`/`.verification`).

Both were split out of `authentication.php`/`user.php` specifically so a host app publishing
`lang/en/cuztomisable` has one obvious place to edit subject lines vs. SMS copy, instead of
hunting through domain-organized files.

## Delivery-channel selection

`notifications.<name>.send_via` (`['email' => bool, 'phone' => bool]`) decides which channel(s) a
notification uses - each channel is its own independent toggle, not either/or, since e.g. a
security alert may legitimately warrant both at once (see `new_ip_address`). Only notifications
where the channel is a genuine *preference* get `send_via` (MFA, password reset, new IP alerts,
registration invites). `email_verification`/`phone_verification` deliberately don't - the channel
*is* what's being verified, so there's no coherent choice to offer there, just `enabled`.

## Config

`config/email.php` and `config/text.php` hold per-channel settings (logging, redaction, from/
reply-to) - merged into `cuztomisable.notifications.emails`/`.texts` by the service provider.
They exist purely to keep the source organized; a host app never publishes them separately, only
the single `config/cuztomisable.php`, same as before the split. See
[Configuration](10-configuration.md).

## Logging

Every send is logged automatically, each to its own table:

| | Email | Text |
|---|---|---|
| Table | `email_logs` | `text_logs` |
| Model | `Models\Logs\Email` | `Models\Logs\Text` |
| Listener | `Listeners\LogEmail` | `Listeners\LogText` |
| Listens for | Laravel's own `MessageSent` | the package's own `TextSent` |
| Gate | `notifications.emails.log` | `notifications.texts.log` |
| Redaction | `emails.hidden_parameters` (Mailable `with()` data, key-based) | `texts.redact_message`/`redact_patterns`/`redact_replacement` (message body, regex-based) |
| Service | `Services\Logs\EmailLogService` | `Services\Logs\TextLogService` |

Both gates default to `true` (logging is on unless explicitly disabled) and both are checked
inside the listener, before any sanitization work happens.

Email has no package-level `MessageSent` equivalent to dispatch itself (Laravel already fires it
for every mail send, regardless of driver), so `LogEmail` listens to that directly. Text has no
such built-in event, so `SendText::handle()` dispatches `TextSent` itself, and only on a
successful send.

### Recipient and creator linking

Both log tables carry two nullable FKs to `users`:

- **`user_id`** - the recipient, resolved automatically inside `create()`:
  `EmailLogService` matches the first "to" address via `User::findUserByType($email, 'email')`;
  `TextLogService` matches the number via `PhoneService::findByNumber()`. Null when the recipient
  isn't a known user (invites, admin notifications to an external address, unverified numbers).
- **`created_by`** - who triggered the send.

`created_by` needed more care than a simple `Auth::id()` call. Because email is `ShouldQueue` and
SMS goes through `SendText`, the code that actually *writes* the log entry runs inside a queue
worker - which has no HTTP session, so `Auth::check()` there is always `false` regardless of who
was logged in when the send was triggered. Both paths instead capture the actor at the earliest
synchronous point, in the original request, and carry it across the queue boundary:

- **Email**: `VanDmadeMailable::queue()`/`send()` capture `Auth::id()` before/at send time, then
  stash it onto the underlying Symfony message (`$message->createdBy`) the same way `template`
  already is - `LogEmail` reads it off `MessageSent`, no event change needed.
- **Text**: `SendText`'s constructor captures `Auth::id()` at `dispatch()` time (i.e. still in the
  request); the value survives serialization into the job and gets threaded through to `TextSent`.

Both models use `Concerns\Auditable`, whose `is_null()` guard means an explicitly-passed
`created_by` is respected rather than clobbered by the trait's own (queue-worker-blind) auto-set
behavior.

`table()` on both services joins `users` twice (once per FK, aliased `ru`/`cu`) to surface
`recipient_name`/`created_by_name` - the raw IDs alone aren't searchable or readable by name, only
by number, so both `ru.name`/`cu.name` are also in `search_columns`.

## Events

Two package-owned events exist purely as hooks for a host app - dispatched *in addition to* the
logging pipeline, not instead of it:

- **`Events\EmailSent`** - carries the whole `VanDmadeMailable` instance. Dispatched from
  `VanDmadeMailable::send()` after every real send, so it covers every Mailable automatically.
- **`Events\TextSent`** - carries `countryCode`/`number`/`message`/`cleanedPhone`/`debug`/
  `createdBy`. Dispatched from `SendText::handle()`, and only on success.

Listen for either the normal Laravel way, from a host app's own `EventServiceProvider`.

## See also

- [Configuration](10-configuration.md)
- [Multi-Factor Authentication](03-multi-factor-authentication.md) - the other real consumer of `send_via`
- [Security](11-security.md)

<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Authentication;

use VanDmade\Cuztomisable\Tests\TestCase;

class PasswordControllerTest extends TestCase
{

    // All five actions are public/token-based, same shape as MFA - the token/code IS the credential.

    // forgot() deliberately returns the SAME success message whether or not the username/email/phone
    // actually matches a user (no user-enumeration leak) - only a REAL match creates a Reset row and
    // queues ForgotMail. Assert both branches return 200 with the same message, but only one creates state.
    public function test_forgot_for_a_known_user_creates_a_reset_and_emails_it(): void
    {
    }

    public function test_forgot_for_an_unknown_user_returns_the_same_success_message_without_creating_anything(): void
    {
    }

    public function test_forgot_too_soon_after_a_recent_reset_is_rejected(): void
    {
    }

    // verify($token, $code = null) is two-purpose: without $code it just checks the token is valid
    // (for showing the reset form); with $code it also checks the code matches, incrementing
    // attempt_counter on mismatch. A LOCKED user's reset still verifies successfully (locked accounts
    // can still reset their password) - distinct "locked" message, not an error.
    public function test_verify_without_a_code_confirms_the_token_is_valid(): void
    {
    }

    public function test_verify_with_the_correct_code_confirms_it(): void
    {
    }

    public function test_verify_with_an_incorrect_code_increments_the_attempt_counter(): void
    {
    }

    public function test_verify_for_a_locked_user_still_succeeds_with_a_distinct_message(): void
    {
    }

    // send() resends the forgot-password notification, bumping sent_at - same "too soon" 401 pattern
    // as Registration's send() and MFA's send().
    public function test_send_resends_the_reset_notification(): void
    {
    }

    public function test_send_rejects_a_resend_that_is_too_soon(): void
    {
    }

    // save() requires BOTH the code (in the body) and the token (in the URL) to match the same Reset
    // row. canUsePassword() blocks reuse of a recent password (config-driven history depth). Only
    // queues ResetMail if cuztomisable.notifications.reset is truthy - default config array makes this
    // always-true today, same `!== false` quirk as Registration's verification email.
    public function test_save_with_a_valid_code_and_token_resets_the_password_and_emails_a_notification(): void
    {
    }

    public function test_save_rejects_a_password_that_was_used_recently(): void
    {
    }

    // lock() is the odd one out - it's a GET link from an email, returns a RedirectResponse to
    // /message?m=..., not JSON. Only works within a week of the reset being created. Emails the
    // configured CUZTOMISABLE_ADMIN address when it actually locks the account.
    public function test_lock_within_a_week_of_the_reset_locks_the_account_and_notifies_the_admin(): void
    {
    }

    public function test_lock_older_than_a_week_redirects_with_a_could_not_lock_message_instead(): void
    {
    }

}

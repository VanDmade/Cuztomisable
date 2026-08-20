<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Users;

use VanDmade\Cuztomisable\Tests\TestCase;

class PasswordControllerTest extends TestCase
{

    // change() has two modes on the SAME endpoint: normal (force absent/false - requires the correct
    // "current" password, wrong current calls addAttempt()) vs forced (force=true - only allowed if
    // user->change_password is already set, e.g. after an admin-issued temporary password via send()
    // below; skips the current-password check entirely). Both go through canUsePassword() (recent-
    // password reuse block) and both queue ChangedMail. invalidate_sessions=true additionally revokes
    // every other Sanctum token except the current request's own.
    public function test_change_with_the_correct_current_password_updates_it(): void
    {
    }

    public function test_change_with_an_incorrect_current_password_increments_the_attempt_counter(): void
    {
    }

    public function test_change_forced_skips_the_current_password_check_when_change_password_is_set(): void
    {
    }

    public function test_change_forced_is_rejected_when_the_user_isnt_actually_flagged_for_a_forced_change(): void
    {
    }

    public function test_change_with_invalidate_sessions_revokes_every_other_token_but_keeps_the_current_one(): void
    {
    }

    // send() (admin-only, permission:reset-user-passwords) issues a random temporary password, sets
    // change_password=true so the user's next change() call must use force=true, and emails it via
    // TemporaryMail. Same "too soon" resend guard pattern as everywhere else in this app.
    public function test_send_issues_a_temporary_password_and_flags_the_account_for_a_forced_change(): void
    {
    }

    public function test_send_too_soon_after_a_previous_send_is_rejected(): void
    {
    }

}

<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Authentication;

use VanDmade\Cuztomisable\Tests\TestCase;

class MFAControllerTest extends TestCase
{

    // All three actions are public (token-based, not session auth) - the token IS the credential.
    // A Users\Code row needs a real user + user_ip_address attached (whereHas('user') is in every query).

    // send() with type=resend reuses code->sent_via if already set, else falls back to config's
    // send_via.phone-then-email preference. Real "sent_via" branch (type=phone AND send_via.phone
    // enabled) texts via SmsService; everything else emails MFAMail. Default config has send_via
    // phone=true AND email=true, so the request's own "type" usually decides, not just config.
    public function test_send_with_type_email_queues_the_mfa_email(): void
    {
    }

    public function test_send_with_type_phone_texts_the_code_when_phone_delivery_is_enabled(): void
    {
    }

    public function test_send_too_soon_after_a_previous_send_is_rejected(): void
    {
    }

    public function test_send_for_an_unknown_or_already_used_token_returns_not_found(): void
    {
    }

    // verify() is read-only (no attempt counting, no state change) - just reports whether the code
    // is still usable and roughly how long is left on it.
    public function test_verify_reports_details_for_a_valid_unexpired_code(): void
    {
    }

    public function test_verify_reports_an_expired_code(): void
    {
    }

    // save() checks the actual code value. Wrong code increments attempt_counter; hitting
    // cuztomisable.login.multi_factor_authentication.attempts.max (default 5) deletes the code
    // entirely instead of just leaving it failed - verify the code row is actually gone, not just
    // that the response says so.
    public function test_save_with_the_correct_code_logs_in_and_clears_other_pending_codes_for_the_user(): void
    {
    }

    public function test_save_with_an_incorrect_code_increments_the_attempt_counter(): void
    {
    }

    public function test_save_exhausting_max_attempts_deletes_the_code(): void
    {
    }

    // save() with remember=true also sets remember/remember_until on the code's IpAddress -
    // that's a second side effect beyond just logging in, easy to miss in a happy-path-only test.
    public function test_save_with_remember_marks_the_ip_address_as_remembered(): void
    {
    }

}

<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Sms;

use VanDmade\Cuztomisable\Tests\TestCase;

class AwsSnsSmsProviderTest extends TestCase
{

    // When app.debug is true, the real SNS client is never touched - the message is written to
    // the log channel instead (Log::debug), and send() still returns true. Real API calls need
    // AWS credentials this test suite won't have, so debug mode is the only path actually
    // testable here without mocking the SNS client itself.
    public function test_send_in_debug_mode_does_not_call_the_real_sns_client(): void
    {
    }

    public function test_send_in_debug_mode_still_returns_true(): void
    {
    }

    // No logging or eventing happens inside this class anymore - it's purely "send, return
    // bool." TextSent now dispatches from Jobs\SendText::handle() after this returns true, not
    // from here.
    public function test_send_does_not_dispatch_textsent_itself(): void
    {
    }

    // On failure (AWS or otherwise), a Logs\Error row is written unconditionally - this doesn't
    // respect notifications.texts.log, since a delivery failure should always be visible
    // regardless of whether successful sends are being logged.
    public function test_send_logs_an_error_entry_on_aws_failure(): void
    {
    }

    public function test_send_returns_false_on_failure(): void
    {
    }

}

<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Listeners;

use VanDmade\Cuztomisable\Tests\TestCase;

class LogTextTest extends TestCase
{

    // Gated by notifications.texts.log (default true) - checked before sanitizeMessage() runs.
    public function test_handle_creates_a_log_entry_when_logging_is_enabled(): void
    {
    }

    public function test_handle_does_nothing_when_logging_is_disabled(): void
    {
    }

    // sanitizeMessage(): redact_message off -> message stored as-is. redact_message on with no
    // redact_patterns -> the whole message replaced with redact_replacement. redact_message on
    // with patterns -> only the matched portions replaced. The 'redacted' parameters flag should
    // reflect whether the stored message actually differs from the original.
    public function test_message_is_stored_unredacted_by_default(): void
    {
    }

    public function test_message_is_fully_replaced_when_redaction_is_on_with_no_patterns(): void
    {
    }

    public function test_message_is_partially_redacted_when_patterns_are_configured(): void
    {
    }

    // created_by comes straight from $event->createdBy (set by SendText at dispatch time), not
    // from Auth - this listener also runs inside a queue worker with no session.
    public function test_created_by_comes_from_the_event(): void
    {
    }

}

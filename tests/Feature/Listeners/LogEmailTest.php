<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Listeners;

use VanDmade\Cuztomisable\Tests\TestCase;

class LogEmailTest extends TestCase
{

    // Gated by notifications.emails.log (default true) - checked before any sanitization work
    // happens, so disabling it should mean zero email_logs rows AND no wasted sanitization.
    public function test_handle_creates_a_log_entry_when_logging_is_enabled(): void
    {
    }

    public function test_handle_does_nothing_when_logging_is_disabled(): void
    {
    }

    // sanitizeSensitiveData() redacts keys matching notifications.emails.hidden_parameters
    // (case-insensitive), recurses into nested arrays, reduces objects with an ->id to just that
    // id, and reduces other objects to their class name - assert each of those four behaviors
    // individually, not just "is redacted somewhere."
    public function test_hidden_parameters_are_redacted_in_the_logged_data(): void
    {
    }

    public function test_redaction_recurses_into_nested_arrays(): void
    {
    }

    public function test_objects_with_an_id_are_reduced_to_just_that_id(): void
    {
    }

    // EmailLogService::create() resolves user_id from "to" - this listener's job is just to pass
    // "to" through correctly (as an array, even for a single recipient), not to do the lookup
    // itself.
    public function test_the_first_to_address_is_passed_through_for_user_resolution(): void
    {
    }

    // created_by comes from $event->message->createdBy (stashed by VanDmadeMailable), not from
    // Auth::check() inside this listener - by the time this listener runs (inside a queue
    // worker for a real ShouldQueue send), there's no session to read.
    public function test_created_by_comes_from_the_stashed_symfony_message_property(): void
    {
    }

}

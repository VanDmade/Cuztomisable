<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Jobs;

use VanDmade\Cuztomisable\Tests\TestCase;

class SendTextTest extends TestCase
{

    // handle() resolves SmsProviderInterface from the container - it should use whatever's
    // config-bound (cuztomisable.sms_provider), not a hardcoded provider, so a host app's custom
    // implementation gets picked up transparently.
    public function test_handle_resolves_the_container_bound_sms_provider(): void
    {
    }

    // createdBy is captured in the constructor (i.e. at dispatch() time, still the original
    // request) rather than read from Auth inside handle(), which runs later inside a queue
    // worker with no session. Assert the captured value survives dispatch -> handle even when
    // Auth::id() would return something different (or null) by the time handle() runs.
    public function test_created_by_is_captured_at_dispatch_time_not_handle_time(): void
    {
    }

    public function test_an_explicit_created_by_overrides_the_authenticated_user(): void
    {
    }

    // TextSent only fires on a successful send - if SmsProviderInterface::send() returns false,
    // nothing should be logged and no event should fire.
    public function test_textsent_dispatches_only_when_the_provider_reports_success(): void
    {
    }

    public function test_textsent_does_not_dispatch_when_the_provider_reports_failure(): void
    {
    }

    // The cleaned phone ('+' + country code + cleanPhone(number)) and app.debug are recomputed
    // here rather than passed back from the provider - assert they match what
    // AwsSnsSmsProvider itself would have produced.
    public function test_textsent_carries_the_cleaned_phone_and_debug_flag(): void
    {
    }

}

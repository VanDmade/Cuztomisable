<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Mail;

use VanDmade\Cuztomisable\Tests\TestCase;

class VanDmadeMailableTest extends TestCase
{

    // createdBy is captured in queue()/send() - both only ever run synchronously, in the
    // original request - specifically so it survives being read later inside a queue worker
    // (no HTTP session there, Auth::id() would be null). Test both paths: a plain ->sendNow()
    // (send() captures it directly) and a real ->send() on a ShouldQueue mailable with
    // QUEUE_CONNECTION=sync (queue() captures it before the job ever runs).
    public function test_send_captures_the_authenticated_user_as_created_by(): void
    {
    }

    public function test_queue_captures_the_authenticated_user_before_the_job_runs(): void
    {
    }

    public function test_created_by_is_null_when_nobody_is_authenticated(): void
    {
    }

    // template and createdBy are both stashed onto the underlying Symfony message via
    // withSymfonyMessage() - MessageSent only exposes the raw message, not the Mailable, so this
    // is how LogEmail gets at either value.
    public function test_send_tags_the_symfony_message_with_the_mailable_class_and_created_by(): void
    {
    }

    // EmailSent should fire with the Mailable instance itself, after every real send - assert it
    // dispatches through the real queued path (ShouldQueue + sync driver), not just ->sendNow().
    public function test_send_dispatches_emailsent_with_the_mailable(): void
    {
    }

    // defaultEnvelope() only sets from/reply-to when the respective config address is non-empty -
    // both should fall back to Laravel's own mailer defaults otherwise.
    public function test_default_envelope_uses_configured_from_and_reply_to_when_set(): void
    {
    }

    public function test_default_envelope_omits_from_and_reply_to_when_not_configured(): void
    {
    }

}

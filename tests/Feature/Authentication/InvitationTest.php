<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Authentication;

use Illuminate\Support\Facades\Mail;
use VanDmade\Cuztomisable\Mail\Users\Invitation as InvitationMail;
use VanDmade\Cuztomisable\Models\Users\Registration;
use VanDmade\Cuztomisable\Tests\Concerns\ActsAsAdmin;
use VanDmade\Cuztomisable\Tests\TestCase;

/**
 * Invitations admin - the invites table, inviting someone, resending and cancelling - all behind
 * permission:invite-users. (Accepting an invite is the registration flow, in RegistrationControllerTest.)
 */
class InvitationTest extends TestCase
{

    use ActsAsAdmin;

    private const TABLE = ['page' => 1, 'size' => 10];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function invitation(array $attributes = []): Registration
    {
        return Registration::create(array_merge(['name' => 'Invitee', 'email' => 'invitee@example.test'], $attributes));
    }

    public function test_every_invite_route_turns_away_a_user_without_the_permission(): void
    {
        $invitation = $this->invitation();
        $this->actingAsUserWithout();

        $this->getJson('/api/invites?'.http_build_query(self::TABLE))->assertForbidden();
        $this->postJson('/api/invite', ['name' => 'X', 'email' => 'x@example.test'])->assertForbidden();
        $this->postJson("/api/invite/{$invitation->id}/send")->assertForbidden();
        $this->deleteJson("/api/invite/{$invitation->id}")->assertForbidden();

        $this->assertDatabaseMissing('user_registrations', ['email' => 'x@example.test']);
        $this->assertNotSoftDeleted($invitation);
        Mail::assertNothingQueued();
    }

    public function test_invite_creates_a_registration_and_emails_the_invitee(): void
    {
        $admin = $this->actingAsUserWith('invite-users');

        $this->postJson('/api/invite', ['name' => 'New Friend', 'email' => 'friend@example.test'])
            ->assertOk()
            ->assertJsonStructure(['message']);

        $invitation = Registration::where('email', 'friend@example.test')->firstOrFail();
        $this->assertSame('New Friend', $invitation->name);
        $this->assertNotEmpty($invitation->code);
        $this->assertNull($invitation->used_at);
        Mail::assertQueued(InvitationMail::class, fn(InvitationMail $mail) => $mail->hasTo('friend@example.test'));
    }

    public function test_invite_requires_a_name_and_an_email(): void
    {
        $this->actingAsUserWith('invite-users');

        $this->postJson('/api/invite', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_inviting_someone_who_already_has_a_pending_invite_is_rejected(): void
    {
        $this->actingAsUserWith('invite-users');
        $this->invitation(['email' => 'friend@example.test']);

        $this->postJson('/api/invite', ['name' => 'Again', 'email' => 'friend@example.test'])
            ->assertJsonPath('message', __('cuztomisable/authentication.registration.errors.recently_invited'));

        $this->assertSame(1, Registration::where('email', 'friend@example.test')->count());
        Mail::assertNothingQueued();
    }

    public function test_inviting_an_email_that_already_has_an_account_is_rejected(): void
    {
        $this->actingAsUserWith('invite-users');
        $this->createUser(['email' => 'member@example.test']);

        $this->postJson('/api/invite', ['name' => 'Member', 'email' => 'member@example.test'])
            ->assertStatus(409)
            ->assertJsonPath('message', __('cuztomisable/authentication.registration.errors.already_used'));

        $this->assertDatabaseMissing('user_registrations', ['email' => 'member@example.test']);
    }

    public function test_table_lists_pending_invitations_but_not_long_expired_ones(): void
    {
        $this->actingAsUserWith('invite-users');
        $this->invitation(['email' => 'pending@example.test']);
        $this->invitation(['email' => 'stale@example.test', 'expires_at' => now()->subDays(3)]);

        $emails = collect($this->getJson('/api/invites?'.http_build_query(self::TABLE))->assertOk()->json('data'))->pluck('email');
        $this->assertTrue($emails->contains('pending@example.test'));
        $this->assertFalse($emails->contains('stale@example.test'));
    }

    public function test_send_resends_the_invitation_and_pushes_out_its_expiry(): void
    {
        $this->actingAsUserWith('invite-users');
        $invitation = $this->invitation(['sent_at' => now()->subHour(), 'expires_at' => now()->addMinute()]);

        $this->postJson("/api/invite/{$invitation->id}/send")->assertOk()->assertJsonStructure(['message', 'resend_in']);

        $invitation->refresh();
        $this->assertTrue($invitation->sent_at->gt(now()->subMinute()));
        Mail::assertQueued(InvitationMail::class, fn(InvitationMail $mail) => $mail->hasTo('invitee@example.test'));
    }

    public function test_send_right_after_the_last_send_is_rejected(): void
    {
        $this->actingAsUserWith('invite-users');
        $invitation = $this->invitation(['sent_at' => now()]);

        $this->postJson("/api/invite/{$invitation->id}/send")
            ->assertStatus(429)
            ->assertJsonPath('message', __('cuztomisable/authentication.registration.errors.sent_recently'));
        Mail::assertNothingQueued();
    }

    public function test_send_on_an_invitation_that_was_already_used_is_rejected(): void
    {
        $this->actingAsUserWith('invite-users');
        $invitation = $this->invitation(['used_at' => now()]);

        $this->postJson("/api/invite/{$invitation->id}/send")->assertNotFound();
        Mail::assertNothingQueued();
    }

    public function test_toggle_delete_cancels_then_restores_with_a_fresh_expiry(): void
    {
        $this->actingAsUserWith('invite-users');
        $invitation = $this->invitation(['expires_at' => now()->subHour()]);

        $this->deleteJson("/api/invite/{$invitation->id}")->assertOk()->assertJsonPath('deleted', false);
        $this->assertSoftDeleted($invitation);

        $this->deleteJson("/api/invite/{$invitation->id}")->assertOk()->assertJsonPath('deleted', true);
        $invitation->refresh();
        $this->assertNull($invitation->deleted_at);
        $this->assertTrue($invitation->expires_at->isFuture());
    }

}

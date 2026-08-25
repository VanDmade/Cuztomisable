<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Authentication;

use VanDmade\Cuztomisable\Tests\TestCase;

class RegistrationControllerTest extends TestCase
{

    // save() with no code: creates a user (locked per cuztomisable.account.locked_by_default),
    // a user_passwords row, and queues VerificationMail - note config('...email_verification')
    // holds an array, so the `!== false` check that gates it is always true today regardless of
    // whether verification is meaningfully "on". Also: these mailables implement ShouldQueue, so
    // Mail::to()->send() actually queues them - assert with Mail::assertQueued(), not assertSent().
    public function test_save_creates_a_locked_user_and_always_sends_the_verification_email(): void
    {
    }

    // save() with a valid invite code: ties the new user to the Registration row (used_at set,
    // created_by copied from the registration's creator), and queues RegisteredMail to the
    // inviter (only when cuztomisable.account.registration.send_notification is true - default).
    public function test_save_with_a_valid_invite_code_ties_the_user_to_the_registration_and_notifies_the_inviter(): void
    {
    }

    // save() with an unknown/expired/used code all collapse into the same generic 404
    // "not_found" - unlike verify(), save() doesn't distinguish why the code lookup failed.
    // Nothing gets created since the whole method runs inside one DB::transaction().
    public function test_save_with_an_unknown_or_already_used_code_returns_a_generic_not_found_error(): void
    {
    }

    // verify() with a valid code returns the registration's name/email/phone for prefilling the form.
    public function test_verify_returns_the_prefilled_registration_details_for_a_valid_code(): void
    {
    }

    // verify() differentiates expired (403) from used (401) from deleted (404) - and every
    // failed verify() call increments attempt_counter via incrementRegistrationAttempt().
    public function test_verify_reports_an_expired_code_and_counts_it_as_an_attempt(): void
    {
    }

    public function test_verify_reports_an_already_used_code(): void
    {
    }

    // Once attempt_counter reaches cuztomisable.account.registration.attempts.max (default 5),
    // the registration gets soft-deleted automatically.
    public function test_verify_exhausting_max_attempts_soft_deletes_the_registration(): void
    {
    }

    // invite()/toggleDelete()/send() all sit behind auth:sanctum + permission:invite-users
    // (routes/api.php). Use Laravel\Sanctum\Sanctum::actingAs($user) - not plain actingAs() - so the
    // auth:sanctum middleware actually resolves the user. Granting the permission itself means
    // creating a Models\Permission row plus a Models\Users\Permission link row (user_id/permission_id).

    // invite() happy path: creates a Registration and queues InvitationMail to the invitee.
    public function test_invite_creates_a_registration_and_emails_the_invitee(): void
    {
    }

    // A second invite to the same still-pending email/phone is rejected (404 "recently_invited").
    public function test_invite_rejects_a_duplicate_pending_invite_for_the_same_email(): void
    {
    }

    // Inviting an email that already belongs to a real user is rejected (409 "already_used").
    public function test_invite_rejects_an_email_already_belonging_to_a_user(): void
    {
    }

    // Without the invite-users permission, CheckPermission middleware aborts with 403.
    public function test_invite_without_the_invite_users_permission_is_forbidden(): void
    {
    }

    // send() resends the invite, bumping sent_at/expires_at and re-queueing InvitationMail.
    public function test_send_resends_the_invitation_and_pushes_out_the_expiration(): void
    {
    }

    // Resending again before cuztomisable.account.registration.resend_after seconds have
    // passed is rejected (429 "sent_recently").
    public function test_send_rejects_a_resend_that_is_too_soon(): void
    {
    }

    // toggleDelete() soft-deletes on first call, then un-deletes (and pushes expires_at forward
    // again) on a second call against an already-deleted row - it's a toggle, not a one-way delete.
    public function test_toggle_delete_soft_deletes_then_restores_a_registration(): void
    {
    }

}

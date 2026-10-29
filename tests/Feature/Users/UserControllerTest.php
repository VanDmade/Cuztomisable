<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Users;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use VanDmade\Cuztomisable\Tests\Concerns\ActsAsAdmin;
use VanDmade\Cuztomisable\Tests\TestCase;

/**
 * Users admin. get()/save()/toggleLocked()/toggleDelete()/toggleMfa() act on the signed-in user when there's no id,
 * and on the given user when there is - which is only reachable through routes gated by view-users / manage-users /
 * toggle-user-mfa, so each admin path is checked both with and without its permission.
 */
class UserControllerTest extends TestCase
{

    use ActsAsAdmin;

    private const TABLE = ['page' => 1, 'size' => 10];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    // --- Viewing ------------------------------------------------------------------------------------------------

    public function test_get_with_no_id_returns_the_authenticated_users_own_profile(): void
    {
        $me = $this->actingAsUserWithout();

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.id', $me->id)
            ->assertJsonPath('user.email', $me->email)
            ->assertJsonStructure(['user', 'change_password', 'permissions', 'needs_to_accept_terms']);
    }

    public function test_get_as_an_admin_with_an_id_returns_that_other_users_profile(): void
    {
        $this->actingAsUserWith('view-users');
        $other = $this->createUser(['name' => 'Someone Else']);

        $this->getJson("/api/user/{$other->id}")
            ->assertOk()
            ->assertJsonPath('user.id', $other->id)
            ->assertJsonPath('user.name', 'Someone Else');
    }

    public function test_get_another_user_without_view_or_manage_users_is_forbidden(): void
    {
        $this->actingAsUserWithout();
        $other = $this->createUser();

        $this->getJson("/api/user/{$other->id}")->assertForbidden();
    }

    public function test_get_an_unknown_user_returns_not_found(): void
    {
        $this->actingAsUserWith('manage-users');
        $this->getJson('/api/user/999999')->assertNotFound();
    }

    public function test_table_lists_users(): void
    {
        $this->actingAsUserWith('view-users');
        $this->createUser(['name' => 'Ada Lovelace']);

        $names = collect($this->getJson('/api/users?'.http_build_query(self::TABLE))->assertOk()->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Ada Lovelace'));
    }

    public function test_table_without_view_or_manage_users_is_forbidden(): void
    {
        $this->actingAsUserWithout();
        $this->getJson('/api/users?'.http_build_query(self::TABLE))->assertForbidden();
    }

    public function test_list_returns_every_user(): void
    {
        $this->actingAsUserWith('view-users');
        $this->createUser();
        $this->createUser();

        $this->getJson('/api/list/users')->assertOk()->assertJsonCount(3, 'list');
    }

    // --- Saving -------------------------------------------------------------------------------------------------

    public function test_save_updates_the_authenticated_users_own_profile(): void
    {
        $me = $this->actingAsUserWithout();

        $this->postJson('/api/profile', ['name' => 'New Name', 'email' => $me->email])
            ->assertOk()
            ->assertJsonPath('user.name', 'New Name');

        $this->assertSame('New Name', $me->fresh()->name);
    }

    public function test_save_requires_a_name_and_a_valid_email(): void
    {
        $this->actingAsUserWithout();

        $this->postJson('/api/profile', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_changing_your_email_clears_its_verification_and_sends_a_new_one(): void
    {
        $me = $this->actingAsUserWithout();
        $this->assertNotNull($me->email_verified_at);

        $this->postJson('/api/profile', ['name' => $me->name, 'email' => 'changed@example.test'])->assertOk();

        $me->refresh();
        $this->assertSame('changed@example.test', $me->email);
        $this->assertNull($me->email_verified_at);
        Mail::assertQueued(Mailable::class, fn(Mailable $mail) => $mail->hasTo('changed@example.test'));
    }

    public function test_save_as_an_admin_updates_another_users_profile(): void
    {
        $this->actingAsUserWith('manage-users');
        $other = $this->createUser(['name' => 'Before']);

        $this->postJson("/api/user/{$other->id}", ['name' => 'After', 'email' => $other->email])
            ->assertOk()
            ->assertJsonPath('user.name', 'After');

        $this->assertSame('After', $other->fresh()->name);
    }

    public function test_an_admin_creates_a_user_who_must_change_the_temporary_password(): void
    {
        $this->actingAsUserWith('manage-users');

        $this->postJson('/api/user', ['name' => 'New Person', 'email' => 'new.person@example.test'])->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'new.person@example.test', 'change_password' => true]);
        Mail::assertQueued(Mailable::class, fn(Mailable $mail) => $mail->hasTo('new.person@example.test'));
    }

    public function test_saving_another_user_without_manage_users_is_forbidden(): void
    {
        $this->actingAsUserWith('view-users');
        $other = $this->createUser(['name' => 'Untouched']);

        $this->postJson("/api/user/{$other->id}", ['name' => 'Changed', 'email' => $other->email])->assertForbidden();
        $this->postJson('/api/user', ['name' => 'Sneaky', 'email' => 'sneaky@example.test'])->assertForbidden();

        $this->assertSame('Untouched', $other->fresh()->name);
        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.test']);
    }

    // --- Locking, attempts, deleting ------------------------------------------------------------------------------

    public function test_toggle_locked_locks_then_unlocks_a_user(): void
    {
        $this->actingAsUserWith('manage-users');
        $other = $this->createUser();

        $this->patchJson("/api/user/{$other->id}/locked")->assertOk()->assertJsonPath('locked', false);
        $this->assertTrue((bool) $other->fresh()->locked);

        $this->patchJson("/api/user/{$other->id}/locked")->assertOk()->assertJsonPath('locked', true);
        $this->assertFalse((bool) $other->fresh()->locked);
    }

    public function test_locking_your_own_account_is_rejected(): void
    {
        $me = $this->actingAsUserWith('manage-users');

        $this->patchJson("/api/user/{$me->id}/locked")
            ->assertJsonPath('message', __('cuztomisable/user.errors.lock_my_account'));
        $this->assertFalse((bool) $me->fresh()->locked);
    }

    public function test_reset_attempts_clears_the_counter_and_unlocks(): void
    {
        $this->actingAsUserWith('manage-users');
        $other = $this->createUser();
        $other->forceFill(['attempts' => 5, 'attempt_timer' => now(), 'locked' => true])->save();

        $this->patchJson("/api/user/{$other->id}/attempts")->assertOk();

        $other->refresh();
        $this->assertSame(0, (int) $other->attempts);
        $this->assertNull($other->attempt_timer);
        $this->assertFalse((bool) $other->locked);
    }

    public function test_toggle_delete_soft_deletes_then_restores_another_user(): void
    {
        $this->actingAsUserWith('manage-users');
        $other = $this->createUser();

        $this->deleteJson("/api/user/{$other->id}")->assertOk()->assertJsonPath('deleted', false);
        $this->assertSoftDeleted($other);

        $this->deleteJson("/api/user/{$other->id}")->assertOk()->assertJsonPath('deleted', true);
        $this->assertNotSoftDeleted($other);
    }

    public function test_toggle_delete_on_your_own_account_is_rejected(): void
    {
        $me = $this->actingAsUserWith('manage-users');

        $this->deleteJson("/api/user/{$me->id}")
            ->assertJsonPath('message', __('cuztomisable/user.errors.delete_my_account'));
        $this->assertNotSoftDeleted($me);
    }

    public function test_locking_or_deleting_without_manage_users_is_forbidden(): void
    {
        $this->actingAsUserWith('view-users');
        $other = $this->createUser();

        $this->patchJson("/api/user/{$other->id}/locked")->assertForbidden();
        $this->deleteJson("/api/user/{$other->id}")->assertForbidden();
        $this->patchJson("/api/user/{$other->id}/attempts")->assertForbidden();

        $this->assertNotSoftDeleted($other);
        $this->assertFalse((bool) $other->fresh()->locked);
    }

    // --- Multi-factor authentication ------------------------------------------------------------------------------

    public function test_toggle_mfa_on_your_own_account_flips_the_flag(): void
    {
        $me = $this->actingAsUserWithout();

        $this->patchJson('/api/mfa')->assertOk();
        $this->assertTrue((bool) $me->fresh()->multi_factor_authentication);

        $this->patchJson('/api/mfa')->assertOk();
        $this->assertFalse((bool) $me->fresh()->multi_factor_authentication);
    }

    public function test_toggle_mfa_on_another_users_account_requires_the_toggle_user_mfa_permission(): void
    {
        $other = $this->createUser();

        $this->actingAsUserWith('manage-users');
        $this->patchJson("/api/user/{$other->id}/mfa")->assertForbidden();
        $this->assertFalse((bool) $other->fresh()->multi_factor_authentication);

        $this->actingAsUserWith('toggle-user-mfa');
        $this->patchJson("/api/user/{$other->id}/mfa")->assertOk();
        $this->assertTrue((bool) $other->fresh()->multi_factor_authentication);
    }

    // --- Signed out -------------------------------------------------------------------------------------------------

    public function test_a_signed_out_visitor_is_turned_away(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
        $this->getJson('/api/users?'.http_build_query(self::TABLE))->assertUnauthorized();
    }

    // refresh()/refreshToken() are the session/mobile token flows, covered with authentication rather than admin;
    // verification()/unsubscribe() exist on the controller but have no routes in routes/api.php.

}

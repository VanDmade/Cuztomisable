<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Users;

use VanDmade\Cuztomisable\Tests\TestCase;

class UserControllerTest extends TestCase
{

    // get($id = null)/save($id = null)/toggleLocked($id = null)/toggleDelete($id = null)/toggleMfa($id = null)
    // all share the same "self vs admin-acting-on-another-user" branch: null id (or a non-admin
    // passing any id) always resolves to Auth::user(), only an id from an ADMIN caller targets someone
    // else. Test both sides of that branch for each action, not just the self case.

    public function test_get_with_no_id_returns_the_authenticated_users_own_profile(): void
    {
    }

    public function test_get_as_an_admin_with_an_id_returns_that_other_users_profile(): void
    {
    }

    public function test_get_as_a_non_admin_with_an_id_is_ignored_and_returns_self_instead(): void
    {
    }

    // table() needs permission:view-users|manage-users. Raw column list is MySQL-specific in places
    // (see IpAddressControllerTest's note on the sibling raw-SQL query) - worth checking this one too
    // once it's actually run against the test DB.
    public function test_table_lists_users(): void
    {
    }

    // save() is also the "/profile" self-update route AND the admin "/user/{id}" route on the same
    // method. Handles name/username/email/timezone/mfa-toggle-via-flag, an optional default phone
    // upsert, an optional default address upsert (only if cuztomisable.account.address isn't disabled),
    // and an image upload OR an explicit clear_image=1 removal via ImageService.
    public function test_save_updates_the_authenticated_users_own_profile(): void
    {
    }

    public function test_save_as_an_admin_updates_another_users_profile(): void
    {
    }

    public function test_save_with_an_uploaded_image_stores_it_via_the_image_service(): void
    {
    }

    public function test_save_with_clear_image_removes_the_existing_image(): void
    {
    }

    // toggleLocked() needs permission:manage-users - not self-service, always targets $id.
    public function test_toggle_locked_locks_then_unlocks_a_user(): void
    {
    }

    // toggleDelete() explicitly forbids deleting your own account (distinct error), separate from the
    // usual not_found check.
    public function test_toggle_delete_soft_deletes_then_restores_another_user(): void
    {
    }

    public function test_toggle_delete_on_your_own_account_is_rejected(): void
    {
    }

    // toggleMfa() has two route entries: PATCH /mfa (self, no permission needed) and
    // PATCH /user/{id}/mfa (permission:toggle-user-mfa) - test both.
    public function test_toggle_mfa_on_your_own_account_flips_the_flag(): void
    {
    }

    public function test_toggle_mfa_on_another_users_account_requires_the_toggle_user_mfa_permission(): void
    {
    }

    // list() is unguarded beyond auth:sanctum - returns every single user (id/name/email as subtitle),
    // no pagination. Worth a comment in the actual PR about whether that's intentional at scale.
    public function test_list_returns_every_user(): void
    {
    }

    // refresh() reissues the auth cookie for the currently logged-in user (web/cookie flow).
    public function test_refresh_reissues_the_auth_cookie(): void
    {
    }

    // refreshToken() is the MOBILE equivalent of refresh() - public route (no auth:sanctum, the
    // refresh token itself is the credential), matches a hashed token against every non-expired,
    // non-revoked RefreshToken row (Hash::check in a loop via ->first(fn...) - not an indexed lookup,
    // worth knowing before this scales). cuztomisable.mobile.refresh.reset_token controls whether a
    // new refresh token is issued or just the expiration gets pushed out - note this reads
    // config('cuztomisable.mobile.*'), and config/mobile.php is the file confirmed NEVER merged into
    // the app (Phase 4's fix target) - so today this always evaluates against the true default (false),
    // never whatever mobile.php actually says. That's real, currently-live behavior to characterize
    // as-is, not something to silently "fix" inside this test.
    public function test_refresh_token_with_a_valid_token_issues_a_new_access_token(): void
    {
    }

    public function test_refresh_token_with_an_expired_or_revoked_token_is_rejected(): void
    {
    }

    // verification()/unsubscribe() exist on the controller but aren't wired up in routes/api.php at all
    // (confirmed via a full read of the file) - dead/unreachable code today. Flag this rather than
    // writing HTTP-level tests against routes that don't exist; if these are meant to come back, that's
    // a routes/api.php fix, not a test-writing task.

}

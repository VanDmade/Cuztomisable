<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Users;

use VanDmade\Cuztomisable\Tests\TestCase;

class AccessControllerTest extends TestCase
{

    // get() needs permission:view-user-roles-permissions|manage-user-roles-permissions (OR).
    // save() needs permission:manage-user-roles-permissions specifically (AND-equivalent, single perm).

    public function test_get_returns_a_users_role_and_permission_ids(): void
    {
    }

    public function test_get_for_an_unknown_user_returns_not_found(): void
    {
    }

    // save() reconciles BOTH roles and permissions in one call, same add/remove-to-match-the-request
    // shape as RoleController::save()'s permission reconciliation - firstOrCreate for additions,
    // delete for anything not in the submitted list, for both roles and permissions independently.
    public function test_save_grants_the_submitted_roles_and_permissions(): void
    {
    }

    public function test_save_revokes_roles_and_permissions_no_longer_in_the_submitted_list(): void
    {
    }

}

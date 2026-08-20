<?php

namespace VanDmade\Cuztomisable\Tests\Feature;

use VanDmade\Cuztomisable\Tests\TestCase;

class RoleControllerTest extends TestCase
{

    // Same auth shape as PermissionControllerTest: get/table/save/toggleDelete/removePermission
    // behind permission:manage-roles-permissions, list() just needs to be logged in.

    // GET /api/role/{id} - eager-loads createdBy and permissions, works withTrashed(), "deleted" bool in response.
    public function test_get_returns_a_role_with_creator_and_linked_permissions(): void
    {
    }

    public function test_get_on_an_unknown_id_returns_not_found(): void
    {
    }

    public function test_table_lists_roles(): void
    {
    }

    // save() reconciles the role's permission set: firstOrCreate (restoring if soft-deleted) for every
    // permission id in the request, then deletes any RolePermission link not in that list. Verify both
    // directions - added AND removed - not just the added side.
    public function test_save_creates_a_role_with_the_given_permissions(): void
    {
    }

    public function test_save_on_an_existing_role_adds_and_removes_permissions_to_match_the_request(): void
    {
    }

    public function test_toggle_delete_soft_deletes_then_restores(): void
    {
    }

    // DELETE /api/role/{id}/permission/{permission} - removes one specific permission link;
    // 404s with a distinct "permission_not_found" message if that link doesn't exist (vs role not found).
    public function test_remove_permission_detaches_one_permission_from_the_role(): void
    {
    }

    public function test_remove_permission_on_a_permission_not_linked_to_the_role_returns_not_found(): void
    {
    }

    // GET /api/list/roles?include_permissions=1 adds a permission_list array per role (pulled from
    // permissionLinks then unset from the response) - without the query param it's omitted entirely.
    public function test_list_without_include_permissions_omits_the_permission_list(): void
    {
    }

    public function test_list_with_include_permissions_adds_a_permission_list_per_role(): void
    {
    }

}

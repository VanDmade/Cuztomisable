<?php

namespace VanDmade\Cuztomisable\Tests\Feature;

use VanDmade\Cuztomisable\Tests\TestCase;

class PermissionControllerTest extends TestCase
{

    // get()/table()/save()/toggleDelete() all sit behind auth:sanctum + permission:manage-roles-permissions
    // (routes/api.php). list() has no permission gate beyond being logged in. Use Sanctum::actingAs()
    // for these, same as RegistrationControllerTest's invite-gated tests.

    // GET /api/permission/{id} - eager-loads createdBy (id/name/email) and roles - works withTrashed(),
    // and the response includes a top-level "deleted" bool from $permission->trashed().
    public function test_get_returns_a_permission_with_creator_and_linked_roles(): void
    {
    }

    public function test_get_on_an_unknown_id_returns_not_found(): void
    {
    }

    // GET /api/permissions - paginated/searchable via TablelifyRequest, columns: id/name/slug/description.
    public function test_table_lists_permissions(): void
    {
    }

    // POST /api/permission (create, $id null) vs POST /api/permission/{id} (update) - same method,
    // branches on whether $id is null. Update on an unknown id throws not_found.
    public function test_save_creates_a_new_permission(): void
    {
    }

    public function test_save_updates_an_existing_permission(): void
    {
    }

    // DELETE /api/permission/{id} - toggle, not one-way: soft-deletes first call, restores second call
    // against an already-trashed row (same shape as Registration's toggleDelete).
    public function test_toggle_delete_soft_deletes_then_restores(): void
    {
    }

    // GET /api/list/permissions (all permissions) vs GET /api/list/role/{id}/permissions (only permissions
    // linked to that role, via Roles\Permission). No permission gate - just auth:sanctum.
    public function test_list_without_a_role_returns_every_permission(): void
    {
    }

    public function test_list_with_a_role_returns_only_that_roles_linked_permissions(): void
    {
    }

}

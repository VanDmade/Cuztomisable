<?php

namespace VanDmade\Cuztomisable\Tests\Feature;

use VanDmade\Cuztomisable\Models\Permission;
use VanDmade\Cuztomisable\Models\Roles\Permission as RolePermission;
use VanDmade\Cuztomisable\Models\Roles\Role;
use VanDmade\Cuztomisable\Models\Users\Role as UserRole;
use VanDmade\Cuztomisable\Tests\Concerns\ActsAsAdmin;
use VanDmade\Cuztomisable\Tests\TestCase;

/**
 * Permissions admin: get/table/save/toggleDelete sit behind permission:manage-roles-permissions; list() only needs
 * a signed-in user.
 */
class PermissionControllerTest extends TestCase
{

    use ActsAsAdmin;

    private const TABLE = ['page' => 1, 'size' => 10];

    private function permission(string $slug): Permission
    {
        return Permission::create(['name' => ucfirst($slug), 'slug' => $slug, 'description' => "Can $slug"]);
    }

    public function test_every_admin_route_turns_away_a_user_without_the_permission(): void
    {
        $permission = $this->permission('read-things');
        $this->actingAsUserWithout();
        $this->getJson("/api/permission/{$permission->id}")->assertForbidden();
        $this->getJson('/api/permissions?'.http_build_query(self::TABLE))->assertForbidden();
        $this->postJson('/api/permission', ['name' => 'X', 'slug' => 'x'])->assertForbidden();
        $this->deleteJson("/api/permission/{$permission->id}")->assertForbidden();
        $this->assertNotSoftDeleted($permission);
    }

    public function test_get_returns_a_permission_with_creator_and_linked_roles(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $permission = $this->permission('read-things');
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $permission->id]);

        $this->getJson("/api/permission/{$permission->id}")
            ->assertOk()
            ->assertJsonPath('permission.slug', 'read-things')
            ->assertJsonPath('permission.roles.0.slug', 'editor')
            ->assertJsonPath('deleted', false);
    }

    public function test_get_on_an_unknown_id_returns_not_found(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $this->getJson('/api/permission/999999')->assertNotFound();
    }

    public function test_table_lists_permissions(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $this->permission('read-things');

        $slugs = collect($this->getJson('/api/permissions?'.http_build_query(self::TABLE))->assertOk()->json('data'))->pluck('slug');
        $this->assertTrue($slugs->contains('read-things'));
        // The signed-in user's own permission is listed too
        $this->assertTrue($slugs->contains('manage-roles-permissions'));
    }

    public function test_save_creates_a_new_permission(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');

        $this->postJson('/api/permission', ['name' => 'Write things', 'slug' => 'write-things', 'description' => 'Writes'])
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('permissions', ['slug' => 'write-things', 'name' => 'Write things', 'description' => 'Writes']);
    }

    public function test_save_updates_an_existing_permission(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $permission = $this->permission('read-things');

        $this->postJson("/api/permission/{$permission->id}", ['name' => 'Read all things', 'slug' => 'read-things', 'description' => null])
            ->assertOk();

        $this->assertSame('Read all things', $permission->fresh()->name);
    }

    public function test_save_rejects_a_slug_another_permission_already_has(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $this->permission('read-things');

        $this->postJson('/api/permission', ['name' => 'Copy', 'slug' => 'read-things'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_save_on_an_unknown_id_returns_not_found(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $this->postJson('/api/permission/999999', ['name' => 'X', 'slug' => 'x', 'description' => null])->assertNotFound();
    }

    public function test_toggle_delete_soft_deletes_then_restores(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $permission = $this->permission('read-things');

        $this->deleteJson("/api/permission/{$permission->id}")->assertOk()->assertJsonPath('deleted', false);
        $this->assertSoftDeleted($permission);

        $this->deleteJson("/api/permission/{$permission->id}")->assertOk()->assertJsonPath('deleted', true);
        $this->assertNotSoftDeleted($permission);
    }

    public function test_list_without_a_role_returns_every_permission(): void
    {
        $this->actingAsUserWithout();
        $this->permission('read-things');
        $this->permission('write-things');

        // Every permission - including the ones the package's migrations seed (e.g. Manage Questionnaire)
        $names = collect($this->getJson('/api/list/permissions')->assertOk()->json('list'))->pluck('name');
        $this->assertSame(Permission::count(), $names->count());
        $this->assertTrue($names->contains('Read-things'));
        $this->assertTrue($names->contains('Write-things'));
    }

    public function test_list_with_a_role_returns_only_that_roles_linked_permissions(): void
    {
        $this->actingAsUserWithout();
        $read = $this->permission('read-things');
        $this->permission('write-things');
        $role = Role::create(['name' => 'Reader', 'slug' => 'reader']);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $read->id]);

        $this->getJson("/api/list/role/{$role->id}/permissions")
            ->assertOk()
            ->assertJsonCount(1, 'list')
            ->assertJsonPath('list.0.id', $read->id);
    }

    public function test_a_role_grants_its_permissions_to_users_who_have_it(): void
    {
        // The permission middleware accepts a permission that comes from a role, not only a direct grant
        $user = $this->actingAsUserWithout();
        $manage = $this->permission('manage-roles-permissions');
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $manage->id]);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id]);

        $this->getJson('/api/permissions?'.http_build_query(self::TABLE))->assertOk();
    }

}

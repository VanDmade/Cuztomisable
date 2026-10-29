<?php

namespace VanDmade\Cuztomisable\Tests\Feature;

use VanDmade\Cuztomisable\Models\Permission;
use VanDmade\Cuztomisable\Models\Roles\Permission as RolePermission;
use VanDmade\Cuztomisable\Models\Roles\Role;
use VanDmade\Cuztomisable\Tests\Concerns\ActsAsAdmin;
use VanDmade\Cuztomisable\Tests\TestCase;

/**
 * Roles admin: get/table/save/toggleDelete/removePermission sit behind permission:manage-roles-permissions; list()
 * only needs a signed-in user.
 */
class RoleControllerTest extends TestCase
{

    use ActsAsAdmin;

    private const TABLE = ['page' => 1, 'size' => 10];

    private function role(string $slug = 'editor', array $permissionIds = []): Role
    {
        $role = Role::create(['name' => ucfirst($slug), 'slug' => $slug, 'description' => "The $slug role"]);
        foreach ($permissionIds as $id) {
            RolePermission::create(['role_id' => $role->id, 'permission_id' => $id]);
        }
        return $role;
    }

    private function permission(string $slug): Permission
    {
        return Permission::create(['name' => ucfirst($slug), 'slug' => $slug]);
    }

    public function test_every_admin_route_turns_away_a_user_without_the_permission(): void
    {
        $role = $this->role();
        $this->actingAsUserWithout();
        $this->getJson("/api/role/{$role->id}")->assertForbidden();
        $this->getJson('/api/roles?'.http_build_query(self::TABLE))->assertForbidden();
        $this->postJson('/api/role', ['name' => 'X', 'slug' => 'x'])->assertForbidden();
        $this->deleteJson("/api/role/{$role->id}")->assertForbidden();
        $this->assertDatabaseMissing('roles', ['slug' => 'x']);
        $this->assertNotSoftDeleted($role);
    }

    public function test_a_signed_out_visitor_is_turned_away(): void
    {
        $this->getJson('/api/roles?'.http_build_query(self::TABLE))->assertUnauthorized();
    }

    public function test_get_returns_a_role_with_creator_and_linked_permissions(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $read = $this->permission('read-things');
        $role = $this->role('editor', [$read->id]);

        $this->getJson("/api/role/{$role->id}")
            ->assertOk()
            ->assertJsonPath('role.slug', 'editor')
            ->assertJsonPath('role.permissions.0.slug', 'read-things')
            ->assertJsonPath('deleted', false);
    }

    public function test_get_on_an_unknown_id_returns_not_found(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $this->getJson('/api/role/999999')->assertNotFound();
    }

    public function test_table_lists_roles(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $this->role('editor');
        $this->role('viewer');

        $response = $this->getJson('/api/roles?'.http_build_query(self::TABLE))->assertOk();
        $slugs = collect($response->json('data'))->pluck('slug');
        $this->assertTrue($slugs->contains('editor'));
        $this->assertTrue($slugs->contains('viewer'));
    }

    public function test_table_searches_by_name(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $this->role('editor');
        $this->role('viewer');

        $response = $this->getJson('/api/roles?'.http_build_query(self::TABLE + ['search' => 'view']))->assertOk();
        $this->assertSame(['viewer'], collect($response->json('data'))->pluck('slug')->all());
    }

    public function test_save_creates_a_role_with_the_given_permissions(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $read = $this->permission('read-things');
        $write = $this->permission('write-things');

        $this->postJson('/api/role', [
            'name' => 'Editor',
            'slug' => 'editor',
            'description' => 'Edits things',
            'permissions' => [$read->id, $write->id],
        ])->assertOk()->assertJsonStructure(['message']);

        $role = Role::where('slug', 'editor')->firstOrFail();
        $this->assertEqualsCanonicalizing([$read->id, $write->id], $role->permissionLinks()->pluck('permission_id')->all());
    }

    public function test_save_requires_a_name_and_a_unique_slug(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $this->role('editor');

        $this->postJson('/api/role', ['slug' => 'editor'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_save_on_an_existing_role_adds_and_removes_permissions_to_match_the_request(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $read = $this->permission('read-things');
        $write = $this->permission('write-things');
        $delete = $this->permission('delete-things');
        $role = $this->role('editor', [$read->id, $write->id]);

        $this->postJson("/api/role/{$role->id}", [
            'name' => 'Editor',
            'slug' => 'editor',
            'description' => null,
            'permissions' => [$write->id, $delete->id],
        ])->assertOk();

        // write kept, delete added, read removed
        $this->assertEqualsCanonicalizing([$write->id, $delete->id], $role->permissionLinks()->pluck('permission_id')->all());
        $this->assertSoftDeleted('role_permissions', ['role_id' => $role->id, 'permission_id' => $read->id]);
    }

    public function test_save_restores_a_permission_link_that_was_removed_before(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $read = $this->permission('read-things');
        $role = $this->role('editor', [$read->id]);
        RolePermission::where('role_id', $role->id)->first()->delete();

        $this->postJson("/api/role/{$role->id}", [
            'name' => 'Editor', 'slug' => 'editor', 'description' => null, 'permissions' => [$read->id],
        ])->assertOk();

        $this->assertSame(1, RolePermission::where('role_id', $role->id)->count());
    }

    public function test_save_with_no_permissions_clears_them_all(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $read = $this->permission('read-things');
        $role = $this->role('editor', [$read->id]);

        $this->postJson("/api/role/{$role->id}", ['name' => 'Editor', 'slug' => 'editor', 'description' => null])->assertOk();

        $this->assertSame(0, $role->permissionLinks()->count());
    }

    public function test_save_on_an_unknown_role_returns_not_found(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $this->postJson('/api/role/999999', ['name' => 'X', 'slug' => 'x', 'description' => null])->assertNotFound();
    }

    public function test_toggle_delete_soft_deletes_then_restores(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $role = $this->role();

        $this->deleteJson("/api/role/{$role->id}")->assertOk()->assertJsonPath('deleted', false);
        $this->assertSoftDeleted($role);

        $this->deleteJson("/api/role/{$role->id}")->assertOk()->assertJsonPath('deleted', true);
        $this->assertNotSoftDeleted($role);
    }

    public function test_remove_permission_detaches_one_permission_from_the_role(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $read = $this->permission('read-things');
        $write = $this->permission('write-things');
        $role = $this->role('editor', [$read->id, $write->id]);

        $this->deleteJson("/api/role/{$role->id}/permission/{$read->id}")->assertOk();

        $this->assertSame([$write->id], $role->permissionLinks()->pluck('permission_id')->all());
    }

    public function test_remove_permission_on_a_permission_not_linked_to_the_role_returns_not_found(): void
    {
        $this->actingAsUserWith('manage-roles-permissions');
        $unlinked = $this->permission('read-things');
        $role = $this->role();

        $this->deleteJson("/api/role/{$role->id}/permission/{$unlinked->id}")
            ->assertNotFound()
            ->assertJsonPath('message', __('cuztomisable/role.errors.permission_not_found'));
    }

    public function test_list_without_include_permissions_omits_the_permission_list(): void
    {
        $this->actingAsUserWithout();
        $this->role();

        $role = $this->getJson('/api/list/roles')->assertOk()->json('list.0');
        $this->assertSame('Editor', $role['name']);
        $this->assertArrayNotHasKey('permission_list', $role);
    }

    public function test_list_with_include_permissions_adds_a_permission_list_per_role(): void
    {
        $this->actingAsUserWithout();
        $read = $this->permission('read-things');
        $this->role('editor', [$read->id]);

        $this->getJson('/api/list/roles?include_permissions=1')
            ->assertOk()
            ->assertJsonPath('list.0.permission_list', [$read->id]);
    }

}

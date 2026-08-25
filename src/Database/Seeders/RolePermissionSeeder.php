<?php

namespace VanDmade\Cuztomisable\Database\Seeders;

use Illuminate\Database\Seeder;
use VanDmade\Cuztomisable\Models\Permission;
use VanDmade\Cuztomisable\Models\Roles\Permission as RolePermission;
use VanDmade\Cuztomisable\Models\Roles\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $administrator = Role::where('slug', 'administrator')->first();
        if ($administrator) {
            foreach (Permission::all() as $permission) {
                RolePermission::firstOrCreate([
                    'role_id' => $administrator->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }
        $rolePermissions = [
            'developer' => ['view-users', 'view-logs', 'view-user-logins', 'clear-user-logins', 'view-user-roles-permissions'],
            'support' => ['view-users', 'manage-users', 'invite-users', 'reset-user-passwords', 'toggle-user-mfa', 'view-user-logins', 'view-user-roles-permissions'],
        ];
        foreach ($rolePermissions as $roleSlug => $permissionSlugs) {
            $role = Role::where('slug', $roleSlug)->first();
            if (! $role) {
                continue;
            }
            foreach ($permissionSlugs as $permissionSlug) {
                $permission = Permission::where('slug', $permissionSlug)->first();
                if (! $permission) {
                    continue;
                }
                RolePermission::firstOrCreate([
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }
    }
}

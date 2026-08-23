<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Administrator gets every permission that exists, including ones added after this
        // seeder was written - dynamic on purpose, so a new permission never needs this file
        // updated by hand to reach the Administrator role.
        $administratorRoleId = DB::table('roles')->where('slug', '=', 'administrator')->value('id');
        foreach (DB::table('permissions')->pluck('id') as $permissionId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $administratorRoleId,
                'permission_id' => $permissionId,
                'created_by' => null,
                'deleted_at' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $rolePermissions = [
            // Developer
            [
                'role_id' => 2,
                'permission_id' => 1,
            ],
            [
                'role_id' => 2,
                'permission_id' => 5,
            ],
            [
                'role_id' => 2,
                'permission_id' => 7,
            ],
            [
                'role_id' => 2,
                'permission_id' => 8,
            ],
            [
                'role_id' => 2,
                'permission_id' => 9,
            ],
            // Support
            [
                'role_id' => 3,
                'permission_id' => 1,
            ],
            [
                'role_id' => 3,
                'permission_id' => 2,
            ],
            [
                'role_id' => 3,
                'permission_id' => 3,
            ],
            [
                'role_id' => 3,
                'permission_id' => 4,
            ],
            [
                'role_id' => 3,
                'permission_id' => 6,
            ],
            [
                'role_id' => 3,
                'permission_id' => 7,
            ],
            [
                'role_id' => 3,
                'permission_id' => 9,
            ],
        ];

        foreach ($rolePermissions as $rp) {
            DB::table('role_permissions')->insertOrIgnore(array_merge($rp, [
                'created_by' => null,
                'deleted_at' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}

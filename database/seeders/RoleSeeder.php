<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'Administrator', 'slug' => 'administrator', 'description' => 'Full access to all user management features, including editing accounts, managing MFA, viewing login history, assigning roles and permissions, and configuring system-level access.'],
            ['id' => 2, 'name' => 'Developer',     'slug' => 'developer',     'description' => 'Has access to technical tools and logs needed for debugging, testing, and maintaining the system. Typically includes permissions like viewing logs, inspecting user activity, and limited management capabilities as required.'],
            ['id' => 3, 'name' => 'Support',       'slug' => 'support',       'description' => 'Limited access focused on assisting users — can view and manage users, reset passwords, invite new users, and review login activity. Cannot assign roles or permissions.'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore(array_merge($role, [
                'created_by' => null,
                'deleted_at' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}

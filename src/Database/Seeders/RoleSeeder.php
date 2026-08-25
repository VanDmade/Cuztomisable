<?php

namespace VanDmade\Cuztomisable\Database\Seeders;

use Illuminate\Database\Seeder;
use VanDmade\Cuztomisable\Models\Roles\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'administrator',
                'description' => 'Full access to all user management features, including editing accounts, managing MFA, viewing login history, assigning roles and permissions, and configuring system-level access.',
            ],
            [
                'name' => 'Developer',
                'slug' => 'developer',
                'description' => 'Has access to technical tools and logs needed for debugging, testing, and maintaining the system. Typically includes permissions like viewing logs, inspecting user activity, and limited management capabilities as required.',
            ],
            [
                'name' => 'Support',
                'slug' => 'support',
                'description' => 'Limited access focused on assisting users — can view and manage users, reset passwords, invite new users, and review login activity. Cannot assign roles or permissions.',
            ],
            [
                'name' => 'Basic User',
                'slug' => 'basic-user',
                'description' => 'Default role for a new member with no elevated permissions. A global default (not tied to any one organization) - available everywhere, alongside whatever roles an organization creates for itself.',
            ],
        ];
        foreach ($roles as $role) {
            Role::updateOrCreate([
                'slug' => $role['slug'],
            ], $role);
        }
    }
}

<?php

namespace VanDmade\Cuztomisable\Database\Seeders;

use Illuminate\Database\Seeder;
use VanDmade\Cuztomisable\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'View Users',
                'slug' => 'view-users',
                'description' => 'Allows viewing the list of users and their details (e.g., name, email, status).',
            ],
            [
                'name' => 'Manage Users',
                'slug' => 'manage-users',
                'description' => 'Grants full control over user accounts, including creating, editing, and deleting users.',
            ],
            [
                'name' => 'Invite Users',
                'slug' => 'invite-users',
                'description' => 'Allows inviting or onboarding new users via email.',
            ],
            [
                'name' => 'Reset User Passwords',
                'slug' => 'reset-user-passwords',
                'description' => 'Allows sending password reset emails to users.',
            ],
            [
                'name' => 'View Logs',
                'slug' => 'view-logs',
                'description' => 'Allows access to system or application logs, including error reports, activity trails, or audit records.',
            ],
            [
                'name' => 'Enable/Disable MFA',
                'slug' => 'toggle-user-mfa',
                'description' => 'Enables toggling multi-factor authentication on or off for any user.',
            ],
            [
                'name' => 'View User Login History',
                'slug' => 'view-user-logins',
                'description' => 'Allows viewing users recent login activity, including IP addresses and device info.',
            ],
            [
                'name' => 'Forget User Devices',
                'slug' => 'clear-user-logins',
                'description' => 'Allows deleting remembered login sessions or trusted devices for users.',
            ],
            [
                'name' => 'View User Roles & Permissions',
                'slug' => 'view-user-roles-permissions',
                'description' => 'Allows viewing which roles and permissions are assigned to each user, without the ability to modify them.',
            ],
            [
                'name' => 'Manage Roles & Permissions',
                'slug' => 'manage-roles-permissions',
                'description' => 'Grants full control over the role and permission system, including creating, editing, and deleting roles and permissions.',
            ],
            [
                'name' => 'Manage User Roles & Permissions',
                'slug' => 'manage-user-roles-permissions',
                'description' => 'Grants the ability to assign or remove roles and permissions from users.',
            ],
            [
                'name' => 'Manage Terms & Conditions',
                'slug' => 'manage-terms',
                'description' => 'Allows uploading and publishing terms & conditions versions, and viewing which users have accepted.',
            ],
        ];
        foreach ($permissions as $permission) {
            Permission::updateOrCreate([
                'slug' => $permission['slug'],
            ], $permission);
        }
    }
}

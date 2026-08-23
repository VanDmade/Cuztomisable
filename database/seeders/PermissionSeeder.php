<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'id' => 1,
                'name' => 'View Users',
                'slug' => 'view-users',
                'description' => 'Allows viewing the list of users and their details (e.g., name, email, status).'
            ],
            [
                'id' => 2,
                'name' => 'Manage Users',
                'slug' => 'manage-users',
                'description' => 'Grants full control over user accounts, including creating, editing, and deleting users.'
            ],
            [
                'id' => 3,
                'name' => 'Invite Users',
                'slug' => 'invite-users',
                'description' => 'Allows inviting or onboarding new users via email.'
            ],
            [
                'id' => 4,
                'name' => 'Reset User Passwords',
                'slug' => 'reset-user-passwords',
                'description' => 'Allows sending password reset emails to users.'
            ],
            [
                'id' => 5,
                'name' => 'View Logs',
                'slug' => 'view-logs',
                'description' => 'Allows access to system or application logs, including error reports, activity trails, or audit records.'
            ],
            [
                'id' => 6,
                'name' => 'Enable/Disable MFA',
                'slug' => 'toggle-user-mfa',
                'description' => 'Enables toggling multi-factor authentication on or off for any user.'
            ],
            [
                'id' => 7,
                'name' => 'View User Login History',
                'slug' => 'view-user-logins',
                'description' => 'Allows viewing users recent login activity, including IP addresses and device info.'
            ],
            [
                'id' => 8,
                'name' => 'Forget User Devices',
                'slug' => 'clear-user-logins',
                'description' => 'Allows deleting remembered login sessions or trusted devices for users.'
            ],
            [
                'id' => 9,
                'name' => 'View User Roles & Permissions',
                'slug' => 'view-user-roles-permissions',
                'description' => 'Allows viewing which roles and permissions are assigned to each user, without the ability to modify them.'
            ],
            [
                'id' => 10,
                'name' => 'Manage Roles & Permissions',
                'slug' => 'manage-roles-permissions',
                'description' => 'Grants full control over the role and permission system, including creating, editing, and deleting roles and permissions.'
            ],
            [
                'id' => 11,
                'name' => 'Manage User Roles & Permissions',
                'slug' => 'manage-user-roles-permissions',
                'description' => 'Grants the ability to assign or remove roles and permissions from users.'
            ],
            [
                'id' => 12,
                'name' => 'Manage Terms & Conditions',
                'slug' => 'manage-terms',
                'description' => 'Allows uploading and publishing terms & conditions versions, and viewing which users have accepted.'
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore(array_merge($permission, [
                'created_by' => null,
                'deleted_at' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}

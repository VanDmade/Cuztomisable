<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $userModel = config('auth.providers.users.model');
        $email = env('CUZTOMISABLE_ADMIN', 'admin@cuztomisable.com');
        $username = env('CUZTOMISABLE_ADMIN_USERNAME', 'admin');
        $password = env('CUZTOMISABLE_ADMIN_PASSWORD', 'Password123!');

        $user = $userModel::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'username' => $username,
                'password' => $password,
                'email_verified_at' => now(),
                'admin' => true,
                'change_password' => true,
                'locked' => false,
                'multi_factor_authentication' => false,
            ]
        );

        $administratorRoleId = DB::table('roles')->where('slug', 'administrator')->value('id');
        if ($administratorRoleId) {
            DB::table('user_roles')->insertOrIgnore([
                'user_id' => $user->id,
                'role_id' => $administratorRoleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command?->info("Admin user ready: {$email} / {$password} (password change required on first login)");
    }
}

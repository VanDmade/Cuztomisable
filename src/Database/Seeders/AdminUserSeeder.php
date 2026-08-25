<?php

namespace VanDmade\Cuztomisable\Database\Seeders;

use Hash;
use Illuminate\Database\Seeder;
use VanDmade\Cuztomisable\Models\Roles\Role;
use VanDmade\Cuztomisable\Models\Users\Role as UserRole;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $userModel = config('auth.providers.users.model');
        $email = env('CUZTOMISABLE_ADMIN', 'admin@cuztomisable.com');
        $username = 'admin';
        $password = 'password';
        $user = $userModel::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'username' => $username,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'admin' => true,
                'change_password' => true,
                'locked' => false,
                'multi_factor_authentication' => false,
            ]
        );
        $administrator = Role::where('slug', 'administrator')->first();
        if ($administrator) {
            UserRole::firstOrCreate([
                'user_id' => $user->id,
                'role_id' => $administrator->id,
            ]);
        }
        $this->command?->info('Admin user ready: {'.$email.'} / {'.$password.'} (password change required on first login)');
    }
}

<?php

namespace VanDmade\Cuztomisable\Tests\Concerns;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use VanDmade\Cuztomisable\Models\Permission;
use VanDmade\Cuztomisable\Models\Users\Permission as UserPermission;
use VanDmade\Cuztomisable\Models\Users\User;

/**
 * Users for the admin tests: plain ones, and signed-in ones holding exactly the permissions a route needs (so a test
 * can also prove the route turns away anyone without them).
 */
trait ActsAsAdmin
{

    protected function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => Str::random(10).'@example.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ], $attributes));
    }

    // Signs in (Sanctum) as a new user holding these permission slugs directly
    protected function actingAsUserWith(string ...$permissions): User
    {
        $user = $this->createUser();
        foreach ($permissions as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => Str::headline($slug)]);
            UserPermission::create(['user_id' => $user->id, 'permission_id' => $permission->id]);
        }
        Sanctum::actingAs($user);
        return $user;
    }

    // Signs in as a user with no permissions at all
    protected function actingAsUserWithout(): User
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);
        return $user;
    }

}

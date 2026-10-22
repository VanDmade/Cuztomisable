<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The permission for managing the questionnaire's questions in the admin area, given to administrators. A migration
// rather than only the seeder, so apps that are already set up get it on their next deploy.
return new class extends Migration
{
    private const SLUG = 'manage-questionnaire';

    public function up(): void
    {
        $permission = DB::table('permissions')->where('slug', self::SLUG)->first();
        $permissionId = $permission->id ?? DB::table('permissions')->insertGetId([
            'name' => 'Manage Questionnaire',
            'slug' => self::SLUG,
            'description' => 'Allows adding, editing, ordering and switching off the questions new and existing users are asked.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $administrator = DB::table('roles')->where('slug', 'administrator')->first();
        if ($administrator && !DB::table('role_permissions')->where('role_id', $administrator->id)->where('permission_id', $permissionId)->exists()) {
            DB::table('role_permissions')->insert([
                'role_id' => $administrator->id,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $permission = DB::table('permissions')->where('slug', self::SLUG)->first();
        if ($permission) {
            DB::table('role_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
};

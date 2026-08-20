<?php

namespace VanDmade\Cuztomisable\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use VanDmade\Cuztomisable\Models\Users as UserModels;

class AccessService
{

    public function get($id): array
    {
        $user = config('auth.providers.users.model')::where('id', '=', $id)->first();
        if (!isset($user->id)) {
            throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
        }
        return [
            'roles' => $user->roles->pluck('id', 'id'),
            'permissions' => $user->permissions->pluck('id', 'id'),
        ];
    }

    public function save(array $data, $id, string $actorId): void
    {
        DB::transaction(function() use ($data, $id, $actorId) {
            $user = config('auth.providers.users.model')::where('id', '=', $id)->first();
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
            }
            // Both fields are nullable - an omitted/empty list means "none", not "leave as-is".
            $roles = $data['roles'] ?? [];
            $permissions = $data['permissions'] ?? [];
            // Iterates through the roles to be added to this specific user
            foreach ($roles as $role) {
                UserModels\Role::firstOrCreate(
                    ['user_id' => $user->id, 'role_id' => $role],
                    ['created_by' => $actorId]
                );
            }
            // Removes older roles from this user that are no longer attached
            $user->roleLinks()
                ->whereNotIn('role_id', $roles)
                ->get()
                ->each
                ->delete();
            // Iterates through the permissions to be added to this specific user
            foreach ($permissions as $permission) {
                UserModels\Permission::firstOrCreate(
                    ['user_id' => $user->id, 'permission_id' => $permission],
                    ['created_by' => $actorId]
                );
            }
            // Removes older permissions from this user that are no longer attached
            $user->permissionLinks()
                ->whereNotIn('permission_id', $permissions)
                ->get()
                ->each
                ->delete();
        });
    }

}

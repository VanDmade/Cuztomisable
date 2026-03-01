<?php

namespace VanDmade\Cuztomisable\Controllers\Users;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use VanDmade\Cuztomisable\Controllers\Controller;
use VanDmade\Cuztomisable\Models\Users as UserModels;
use VanDmade\Cuztomisable\Requests\Users\AccessRequest;

class AccessController extends Controller
{

    public function get($id): JsonResponse
    {
        try {
            $user = config('auth.providers.users.model')::where('id', '=', $id)->first();
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
            }
            return $this->success([
                'access' => [
                    'roles' => $user->roles->pluck('id', 'id'),
                    'permissions' => $user->permissions->pluck('id', 'id'),
                ],
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(AccessRequest $request, $id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                $this->rateLimit(
                    'cuztomisable:access:save:'.implode(':', [$this->actorId(), (string) $id]),
                    'cuztomisable/user.errors.not_found'
                );
                $data = $request->validated();
                $user = config('auth.providers.users.model')::where('id', '=', $id)->first();
                if (!isset($user->id)) {
                    throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
                }
                // Iterates through the roles to be added to this specific role
                foreach ($data['roles'] as $role) {
                    UserModels\Role::firstOrCreate(
                        ['user_id' => $user->id, 'role_id' => $role],
                        ['created_by' => $this->actorId()]
                    );
                }
                // Removes older roles from this role that are not longer attached
                $user->roleLinks()
                    ->whereNotIn('role_id', $data['roles'])
                    ->get()
                    ->each
                    ->delete();
                // Iterates through the permissions to be added to this specific role
                foreach ($data['permissions'] as $permission) {
                    UserModels\Permission::firstOrCreate(
                        ['user_id' => $user->id, 'permission_id' => $permission],
                        ['created_by' => $this->actorId()]
                    );
                }
                // Removes older permissions from this role that are not longer attached
                $user->permissionLinks()
                    ->whereNotIn('permission_id', $data['permissions'])
                    ->get()
                    ->each
                    ->delete();
                return $this->success([
                    'message' => __('cuztomisable/user.access.saved'),
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}
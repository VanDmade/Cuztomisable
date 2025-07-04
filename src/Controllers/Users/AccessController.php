<?php

namespace VanDmade\Cuztomisable\Controllers\Users;

use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Requests\Users\AccessRequest;
use VanDmade\Cuztomisable\Controllers\Controller;
use VanDmade\Cuztomisable\Models\Users as UserModels;
use Auth;
use Exception;
use Hash;

class AccessController extends Controller
{

    public function get($id)
    {
        try {
            $user = UserModels\User::where('id', '=', $id)->first();
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

    public function save(AccessRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $user = UserModels\User::where('id', '=', $id)->first();
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
            }
            $delete = [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => Auth::user()->id,
            ];
            // Iterates through the roles to be added to this specific role
            foreach ($data['roles'] as $i => $role) {
                UserModels\Role::firstOrCreate(
                    ['user_id' => $user->id, 'role_id' => $role],
                    ['created_by' => Auth::user()->id]
                );
            }
            // Removes older roles from this role that are not longer attached
            $user->roleLinks()
                ->whereNotIn('role_id', $data['roles'])
                ->update($delete);
            // Iterates through the permissions to be added to this specific role
            foreach ($data['permissions'] as $i => $permission) {
                UserModels\Permission::firstOrCreate(
                    ['user_id' => $user->id, 'permission_id' => $permission],
                    ['created_by' => Auth::user()->id]);
            }
            // Removes older permissions from this role that are not longer attached
            $user->permissionLinks()
                ->whereNotIn('permission_id', $data['permissions'])
                ->update($delete);
            return $this->success([
                'message' => __('cuztomisable/user.access.saved'),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}
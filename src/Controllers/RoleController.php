<?php

namespace VanDmade\Cuztomisable\Controllers;

use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Requests\RoleRequest;
use VanDmade\Cuztomisable\Requests\TablelifyRequest;
use VanDmade\Cuztomisable\Models\Roles;
use VanDmade\Cuztomisable\Models\Permission;
use VanDmade\Cuztomisable\Helpers\Tablelify;
use Auth;
use DB;
use Exception;

class RoleController extends Controller
{

    public function get($id)
    {
        try {
            $role = Roles\Role::select('id', 'name', 'slug', 'description', 'created_by')
                ->with([
                    'createdBy' => fn($query) => $query->select('id', 'name', 'email'),
                    'permissions' => fn($query) => $query->select('permissions.id', 'permissions.name', 'permissions.slug', 'permissions.description'),
                ])
                ->where('id', '=', $id)
                ->withTrashed()
                ->first();
            if (!isset($role->id)) {
                throw new Exception(__('cuztomisable/role.errors.not_found'), 404);
            }
            return $this->success([
                'role' => $role,
                'deleted' => $role->trashed(),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function table(TablelifyRequest $request)
    {
        try {
            $data = $request->validated();
            $query = Roles\Role::select('id', 'name', 'slug', 'description')
                ->where(function($query) use ($data) {
                    $query->orWhere('name', 'LIKE', $data['search'])
                        ->orWhere('slug', 'LIKE', $data['search']);
                });
            return Tablelify::run($query, $data);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(RoleRequest $request, $id = null)
    {
        try {
            $data = $request->validated();
            if (is_null($id)) {
                $role = new Roles\Role();
            } else {
                $role = Roles\Role::where('id', '=', $id)->first();
                if (!isset($role->id)) {
                    throw new Exception(__('cuztomisable/role.errors.not_found'), 404);
                }
            }
            $role->name = $data['name'];
            $role->slug = $data['slug'];
            $role->description = $data['description'];
            $role->save();
            // Iterates through the permissions to be added to this specific role
            foreach (Permission::whereIn('id', $data['permissions'])->get() as $i => $permission) {
                Roles\Permission::firstOrCreate([
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ], [
                    'created_by' => Auth::user()->id,
                ]);
            }
            // Removes older permissions from this role that are not longer attached
            $role->permissionLinks()
                ->whereNotIn('permission_id', $data['permissions'])
                ->update([
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'deleted_by' => Auth::user()->id,
                ]);
            return $this->success([
                'message' => __('cuztomisable/role.'.(is_null($id) ? 'created' : 'saved')),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete($id)
    {
        try {
            $role = Roles\Role::where('id', '=', $id)->withTrashed()->first();
            if (!isset($role->id)) {
                throw new Exception(__('cuztomisable/role.errors.not_found'), 404);
            }
            $deleted = $role->trashed();
            // Resets OR sets the deleted at parameters for soft deletion
            $role->deleted_by = $deleted ? null : Auth::user()->id;
            $role->deleted_at = $deleted ? null : date('Y-m-d H:i:s');
            $role->save();
            return $this->success([
                'message' => '',
                'deleted' => $deleted,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function removePermission($id, $permission)
    {
        try {
            $role = Roles\Role::where('id', '=', $id)->first();
            if (!isset($role->id)) {
                throw new Exception(__('cuztomisable/role.errors.not_found'), 404);
            }
            $permission = $role->permissionLinks()->where('permission_id', '=', $permission)->first();
            if (!isset($permission->id)) {
                throw new Exception(__('cuztomisable/role.errors.permission_not_found'), 404);
            }
            $permission->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => Auth::user()->id,
            ]);
            return $this->success([
                'message' => __('cuztomisable/role.permission_removed'),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function list()
    {
        return $this->success([
            'list' => Roles\Role::select('id', 'name', 'description as subtitle')->get(),
        ]);
    }

}

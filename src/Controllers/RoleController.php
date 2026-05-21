<?php

namespace VanDmade\Cuztomisable\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use VanDmade\Cuztomisable\Requests\RoleRequest;
use VanDmade\Cuztomisable\Requests\TablelifyRequest;
use VanDmade\Cuztomisable\Models\Roles;
use VanDmade\Cuztomisable\Models\Permission;
use VanDmade\Cuztomisable\Models\Roles\Permission as RolePermission;
use VanDmade\Cuztomisable\Helpers\Tablelify;

class RoleController extends Controller
{

    public function get($id): JsonResponse
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

    public function table(TablelifyRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $query = Roles\Role::select('id', 'name', 'slug', 'description');
            $parameters = [
                'allowed_columns' => ['id', 'name', 'slug', 'description'],
                'search_columns' => ['name', 'slug'],
                'allowed_filters' => ['id', 'slug', 'name'],
                'default_columns' => ['id' => 'desc'],
            ];
            return Tablelify::run($query, array_merge($data, $parameters));
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(RoleRequest $request, $id = null): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                $this->rateLimit(
                    'cuztomisable:roles:save:'.implode(':', [$this->actorId(), (string) ($id ?? 'new')]),
                    'cuztomisable/role.errors.not_found'
                );
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
                foreach (Permission::whereIn('id', $data['permissions'])->get() as $permission) {
                    $link = RolePermission::withTrashed()->firstOrCreate([
                        'role_id' => $role->id,
                        'permission_id' => $permission->id,
                    ], [
                        'created_by' => $this->actorId(),
                    ]);
                    if ($link->trashed()) {
                        $link->restore();
                    }
                }
                // Removes older permissions from this role that are not longer attached
                $role->permissionLinks()
                    ->whereNotIn('permission_id', $data['permissions'])
                    ->get()
                    ->each
                    ->delete();
                return $this->success([
                    'message' => __('cuztomisable/role.'.(is_null($id) ? 'created' : 'saved')),
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete($id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                $this->rateLimit(
                    'cuztomisable:roles:toggle_delete:'.implode(':', [$this->actorId(), (string) $id]),
                    'cuztomisable/role.errors.not_found'
                );
                $role = Roles\Role::where('id', '=', $id)->withTrashed()->first();
                if (!isset($role->id)) {
                    throw new Exception(__('cuztomisable/role.errors.not_found'), 404);
                }
                $deleted = $role->trashed();
                if ($deleted) {
                    $role->restore();
                } else {
                    $role->delete();
                }
                return $this->success([
                    'message' =>  __('cuztomisable/role.'.($deleted ? 'restored' : 'deleted')),
                    'deleted' => $deleted,
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function removePermission($id, $permission): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id, $permission) {
                $this->rateLimit(
                    'cuztomisable:roles:remove_permission:'.implode(':', [$this->actorId(), (string) $id, (string) $permission]),
                    'cuztomisable/role.errors.permission_not_found'
                );
                $role = Roles\Role::where('id', '=', $id)->first();
                if (!isset($role->id)) {
                    throw new Exception(__('cuztomisable/role.errors.not_found'), 404);
                }
                $permissionLink = $role->permissionLinks()->where('permission_id', '=', $permission)->first();
                if (!isset($permissionLink->id)) {
                    throw new Exception(__('cuztomisable/role.errors.permission_not_found'), 404);
                }
                $permissionLink->delete();
                return $this->success([
                    'message' => __('cuztomisable/role.permission_removed'),
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function list(Request $request): JsonResponse
    {
        try {
            $includePermissions = $request->has('include_permissions');
            $roles = Roles\Role::select('id', 'name', 'description as subtitle');
            if ($includePermissions) {
                $roles->with('permissionLinks:id,permission_id,role_id');
            }
            $roles = $roles->get();
            if ($includePermissions) {
                $roles->each(function ($role) {
                    $role->permission_list = $role->permissionLinks->pluck('permission_id');
                    unset($role->permissionLinks);
                });
            }
            return $this->success([
                'list' => $roles,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

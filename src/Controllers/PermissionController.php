<?php

namespace VanDmade\Cuztomisable\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use VanDmade\Cuztomisable\Requests\PermissionRequest;
use VanDmade\Cuztomisable\Requests\TablelifyRequest;
use VanDmade\Cuztomisable\Models\Permission;
use VanDmade\Cuztomisable\Models\Roles\Permission as RolePermission;
use VanDmade\Cuztomisable\Helpers\Tablelify;

class PermissionController extends Controller
{

    public function get($id): JsonResponse
    {
        try {
            $permission = Permission::select('id', 'name', 'slug', 'description', 'created_by')
                ->with([
                    'createdBy' => fn($query)  => $query->select('id', 'name', 'email'),
                    'roles' => fn($query) => $query->select('roles.id', 'roles.name', 'roles.slug', 'roles.description'),
                ])
                ->where('id', '=', $id)
                ->withTrashed()
                ->first();
            if (!isset($permission->id)) {
                throw new Exception(__('cuztomisable/permission.errors.not_found'), 404);
            }
            return $this->success([
                'permission' => $permission,
                'deleted' => $permission->trashed(),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function table(TablelifyRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $query = Permission::select('id', 'name', 'slug', 'description');
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

    public function save(PermissionRequest $request, $id = null): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                $this->rateLimit(
                    'cuztomisable:permissions:save:'.implode(':', [$this->actorId(), (string) ($id ?? 'new')]),
                    'cuztomisable/permission.errors.not_found'
                );
                $data = $request->validated();
                if (is_null($id)) {
                    $permission = new Permission();
                } else {
                    $permission = Permission::find($id);
                    if (!isset($permission->id)) {
                        throw new Exception(__('cuztomisable/permission.errors.not_found'), 404);
                    }
                }
                $permission->name = $data['name'];
                $permission->slug = $data['slug'];
                $permission->description = $data['description'];
                $permission->save();
                return $this->success([
                    'message' => '',
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
                    'cuztomisable:permissions:toggle_delete:'.implode(':', [$this->actorId(), (string) $id]),
                    'cuztomisable/permission.errors.not_found'
                );
                $permission = Permission::where('id', '=', $id)->withTrashed()->first();
                if (!isset($permission->id)) {
                    throw new Exception(__('cuztomisable/permission.errors.not_found'), 404);
                }
                $deleted = $permission->trashed();
                if ($deleted) {
                    $permission->restore();
                } else {
                    $permission->delete();
                }
                return $this->success([
                    'message' => '',
                    'deleted' => $deleted,
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function list($role = null): JsonResponse
    {
        try {
            $permissions = [];
            if (!is_null($role)) {
                // Gets the list of permissions that are associated with the role that is given
                $permissions = RolePermission::select('id', 'permission_id')
                    ->where('role_id', '=', $role)
                    ->get()
                    ->pluck('permission_id');
            }
            $list = Permission::select('id', 'name', 'description as subtitle')
                ->where(function($query) use ($role, $permissions) {
                    if (!is_null($role)) {
                        $query->whereIn('id', $permissions);
                    }
                })
                ->get();
            return $this->success([
                'list' => $list,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

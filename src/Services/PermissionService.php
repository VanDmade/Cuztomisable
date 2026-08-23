<?php

namespace VanDmade\Cuztomisable\Services;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use VanDmade\Cuztomisable\Models\Permission;
use VanDmade\Cuztomisable\Models\Roles\Permission as RolePermission;

class PermissionService
{

    public function find($id): Permission
    {
        $permission = Permission::select('id', 'name', 'slug', 'description', 'created_by')
            ->with([
                'createdBy' => fn($query) => $query->select('id', 'name', 'email'),
                'roles' => fn($query) => $query->select('roles.id', 'roles.name', 'roles.slug', 'roles.description'),
            ])
            ->where('id', '=', $id)
            ->withTrashed()
            ->first();
        if (!isset($permission->id)) {
            throw new Exception(__('cuztomisable/permission.errors.not_found'), 404);
        }
        return $permission;
    }

    public function table(array $data): JsonResponse
    {
        $query = Permission::select('id', 'name', 'slug', 'description');
        $parameters = [
            'allowed_columns' => ['id', 'name', 'slug', 'description'],
            'search_columns' => ['name', 'slug'],
            'allowed_filters' => ['id', 'slug', 'name'],
            'default_columns' => ['id' => 'desc'],
        ];
        return TableService::generate($query, array_merge($data, $parameters));
    }

    public function save(array $data, $id = null): bool
    {
        return DB::transaction(function() use ($data, $id) {
            $created = is_null($id);
            if ($created) {
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
            return $created;
        });
    }

    public function toggleDelete($id): bool
    {
        return DB::transaction(function() use ($id) {
            $permission = Permission::where('id', '=', $id)->withTrashed()->first();
            if (!isset($permission->id)) {
                throw new Exception(__('cuztomisable/permission.errors.not_found'), 404);
            }
            $deleted = $permission->trashed();
            if ($deleted) {
                $permission->undo();
            } else {
                $permission->delete();
            }
            return $deleted;
        });
    }

    public function list($role = null): Collection
    {
        $permissionIds = [];
        if (!is_null($role)) {
            // Gets the list of permissions that are associated with the role that is given
            $permissionIds = RolePermission::select('id', 'permission_id')
                ->where('role_id', '=', $role)
                ->get()
                ->pluck('permission_id');
        }
        return Permission::select('id', 'name', 'description as subtitle')
            ->where(function($query) use ($role, $permissionIds) {
                if (!is_null($role)) {
                    $query->whereIn('id', $permissionIds);
                }
            })
            ->get();
    }

}

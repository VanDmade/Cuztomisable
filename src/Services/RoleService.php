<?php

namespace VanDmade\Cuztomisable\Services;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use VanDmade\Cuztomisable\Models\Permission;
use VanDmade\Cuztomisable\Models\Roles;
use VanDmade\Cuztomisable\Models\Roles\Permission as RolePermission;

class RoleService
{

    public function find($id): Roles\Role
    {
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
        return $role;
    }

    public function table(array $data): JsonResponse
    {
        $query = Roles\Role::select('id', 'name', 'slug', 'description');
        $parameters = [
            'allowed_columns' => ['id', 'name', 'slug', 'description'],
            'search_columns' => ['name', 'slug'],
            'allowed_filters' => ['id', 'slug', 'name'],
            'default_columns' => ['id' => 'desc'],
        ];
        return TableService::generate($query, array_merge($data, $parameters));
    }

    public function save(array $data, $id, string $actorId): bool
    {
        return DB::transaction(function() use ($data, $id, $actorId) {
            $created = is_null($id);
            if ($created) {
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
            // The permissions field is nullable - an omitted/empty list means "no permissions",
            // not "leave existing permissions untouched".
            $permissions = $data['permissions'] ?? [];
            // Iterates through the permissions to be added to this specific role
            foreach (Permission::whereIn('id', $permissions)->get() as $permission) {
                $link = RolePermission::withTrashed()->firstOrCreate([
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ], [
                    'created_by' => $actorId,
                ]);
                if ($link->trashed()) {
                    $link->undo();
                }
            }
            // Removes older permissions from this role that are no longer attached
            $role->permissionLinks()
                ->whereNotIn('permission_id', $permissions)
                ->get()
                ->each
                ->delete();
            return $created;
        });
    }

    public function toggleDelete($id): bool
    {
        return DB::transaction(function() use ($id) {
            $role = Roles\Role::where('id', '=', $id)->withTrashed()->first();
            if (!isset($role->id)) {
                throw new Exception(__('cuztomisable/role.errors.not_found'), 404);
            }
            $deleted = $role->trashed();
            if ($deleted) {
                $role->undo();
            } else {
                $role->delete();
            }
            return $deleted;
        });
    }

    public function removePermission($id, $permission): void
    {
        DB::transaction(function() use ($id, $permission) {
            $role = Roles\Role::where('id', '=', $id)->first();
            if (!isset($role->id)) {
                throw new Exception(__('cuztomisable/role.errors.not_found'), 404);
            }
            $permissionLink = $role->permissionLinks()->where('permission_id', '=', $permission)->first();
            if (!isset($permissionLink->id)) {
                throw new Exception(__('cuztomisable/role.errors.permission_not_found'), 404);
            }
            $permissionLink->delete();
        });
    }

    public function list(Request $request): Collection
    {
        $includePermissions = $request->has('include_permissions');
        $roles = Roles\Role::select('id', 'name', 'description as subtitle');
        if ($includePermissions) {
            $roles->with('permissionLinks:id,permission_id,role_id');
        }
        $roles = $roles->get();
        if ($includePermissions) {
            $roles->each(function($role) {
                $role->permission_list = $role->permissionLinks->pluck('permission_id');
                unset($role->permissionLinks);
            });
        }
        return $roles;
    }

}

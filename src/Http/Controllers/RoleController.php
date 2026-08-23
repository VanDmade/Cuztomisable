<?php

namespace VanDmade\Cuztomisable\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use VanDmade\Cuztomisable\Http\Requests\RoleRequest;
use VanDmade\Cuztomisable\Http\Requests\TableRequest;
use VanDmade\Cuztomisable\Services\RoleService;

class RoleController extends CuztomisableController
{

    public function __construct(
        protected readonly RoleService $roleService
    ) {
    }

    public function get($id): JsonResponse
    {
        try {
            $role = $this->roleService->find($id);
            return $this->success([
                'role' => $role,
                'deleted' => $role->trashed(),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function table(TableRequest $request): JsonResponse
    {
        try {
            return $this->roleService->table($request->validated());
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function save(RoleRequest $request, $id = null): JsonResponse
    {
        try {
            $created = $this->roleService->save($request->validated(), $id, $this->actorId());
            return $this->success([
                'message' => __('cuztomisable/role.'.($created ? 'created' : 'saved')),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete($id): JsonResponse
    {
        try {
            $deleted = $this->roleService->toggleDelete($id);
            return $this->success([
                'message' => __('cuztomisable/role.'.($deleted ? 'restored' : 'deleted')),
                'deleted' => $deleted,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function removePermission($id, $permission): JsonResponse
    {
        try {
            $this->roleService->removePermission($id, $permission);
            return $this->success([
                'message' => __('cuztomisable/role.permission_removed'),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function list(Request $request): JsonResponse
    {
        try {
            return $this->success([
                'list' => $this->roleService->list($request),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

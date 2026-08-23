<?php

namespace VanDmade\Cuztomisable\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Throwable;
use VanDmade\Cuztomisable\Http\Requests\PermissionRequest;
use VanDmade\Cuztomisable\Http\Requests\TableRequest;
use VanDmade\Cuztomisable\Services\PermissionService;

class PermissionController extends CuztomisableController
{

    public function __construct(
        protected readonly PermissionService $permissionService
    ) {
    }

    public function get($id): JsonResponse
    {
        try {
            $permission = $this->permissionService->find($id);
            return $this->success([
                'permission' => $permission,
                'deleted' => $permission->trashed(),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function table(TableRequest $request): JsonResponse
    {
        try {
            return $this->permissionService->table($request->validated());
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function save(PermissionRequest $request, $id = null): JsonResponse
    {
        try {
            $created = $this->permissionService->save($request->validated(), $id);
            return $this->success([
                'message' => __('cuztomisable/permission.'.($created ? 'created' : 'saved')),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete($id): JsonResponse
    {
        try {
            $deleted = $this->permissionService->toggleDelete($id);
            return $this->success([
                'message' => __('cuztomisable/permission.'.($deleted ? 'restored' : 'deleted')),
                'deleted' => $deleted,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function list($role = null): JsonResponse
    {
        try {
            return $this->success([
                'list' => $this->permissionService->list($role),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

<?php

namespace VanDmade\Cuztomisable\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
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
            $permission = $this->permissionService->get($id);
            return $this->success([
                'permission' => $permission,
                'deleted' => $permission->trashed(),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function table(TableRequest $request): JsonResponse
    {
        try {
            return $this->permissionService->table($request->validated());
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(PermissionRequest $request, $id = null): JsonResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:permissions:save:'.implode(':', [$this->actorId(), (string) ($id ?? 'new')]),
                'cuztomisable/permission.errors.not_found'
            );
            $created = $this->permissionService->save($request->validated(), $id);
            return $this->success([
                'message' => __('cuztomisable/permission.'.($created ? 'created' : 'saved')),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete($id): JsonResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:permissions:toggle_delete:'.implode(':', [$this->actorId(), (string) $id]),
                'cuztomisable/permission.errors.not_found'
            );
            $deleted = $this->permissionService->toggleDelete($id);
            return $this->success([
                'message' => __('cuztomisable/permission.'.($deleted ? 'restored' : 'deleted')),
                'deleted' => $deleted,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function list($role = null): JsonResponse
    {
        try {
            return $this->success([
                'list' => $this->permissionService->list($role),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

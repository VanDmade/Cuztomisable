<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Users;

use Illuminate\Http\JsonResponse;
use Throwable;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\Users\AccessRequest;
use VanDmade\Cuztomisable\Services\Users\AccessService;

/**
 * Handles adding to and grabbing the roles and permissions for a specific user
 */
class AccessController extends CuztomisableController
{

    public function __construct(
        protected readonly AccessService $accessService
    ) {
    }

    public function get($id): JsonResponse
    {
        try {
            return $this->success([
                'access' => $this->accessService->find($id),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function save(AccessRequest $request, $id): JsonResponse
    {
        try {
            $this->accessService->save($request->validated(), $id, $this->actorId());
            return $this->success([
                'message' => __('cuztomisable/user.access.saved'),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Users;

use Exception;
use Illuminate\Http\JsonResponse;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\Users\AccessRequest;
use VanDmade\Cuztomisable\Services\AccessService;

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
                'access' => $this->accessService->get($id),
            ]);
        } catch (Exception $error) {
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
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Users;

use Illuminate\Http\JsonResponse;
use Throwable;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\TableRequest;
use VanDmade\Cuztomisable\Http\Requests\Users\PhoneRequest;
use VanDmade\Cuztomisable\Services\PhoneService;

/**
 * Handles a user's phone number management operations.
 */
class PhoneController extends CuztomisableController
{

    public function __construct(
        protected readonly PhoneService $phoneService
    ) {
    }

    public function get($id): JsonResponse
    {
        try {
            return $this->success([
                'phone' => $this->phoneService->find($id),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function table(TableRequest $request, $id = null): JsonResponse
    {
        try {
            return $this->phoneService->table($request->validated(), $id);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function save(PhoneRequest $request, $id = null): JsonResponse
    {
        try {
            $phone = $this->phoneService->save($request->validated(), $id);
            return $this->success([
                'message' => __('cuztomisable/user.phone.'.($id === null ? 'created' : 'saved')),
                'phone' => $phone,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function makeDefault($id): JsonResponse
    {
        try {
            $phone = $this->phoneService->makeDefault($id);
            return $this->success([
                'message' => __('cuztomisable/user.phone.default'),
                'phone' => $phone,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete($id): JsonResponse
    {
        try {
            $deleted = $this->phoneService->toggleDelete($id);
            return $this->success([
                'message' => __('cuztomisable/user.phone.'.($deleted ? 'undo' : 'deleted')),
                'deleted' => $deleted,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

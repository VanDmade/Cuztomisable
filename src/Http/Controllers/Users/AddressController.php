<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Users;

use Illuminate\Http\JsonResponse;
use Throwable;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\TableRequest;
use VanDmade\Cuztomisable\Http\Requests\Users\AddressRequest;
use VanDmade\Cuztomisable\Services\AddressService;

/**
 * Handles a user's address management operations.
 */
class AddressController extends CuztomisableController
{

    public function __construct(
        protected readonly AddressService $addressService
    ) {
    }

    public function get($id): JsonResponse
    {
        try {
            return $this->success([
                'address' => $this->addressService->find($id),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function table(TableRequest $request, $id = null): JsonResponse
    {
        try {
            return $this->addressService->table($request->validated(), $id);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function save(AddressRequest $request, $id = null): JsonResponse
    {
        try {
            $address = $this->addressService->save($request->validated(), $id);
            return $this->success([
                'message' => __('cuztomisable/user.address.'.($id === null ? 'created' : 'saved')),
                'address' => $address,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function makeDefault($id): JsonResponse
    {
        try {
            $address = $this->addressService->makeDefault($id);
            return $this->success([
                'message' => __('cuztomisable/user.address.default'),
                'address' => $address,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete($id): JsonResponse
    {
        try {
            $deleted = $this->addressService->toggleDelete($id);
            return $this->success([
                'message' => __('cuztomisable/user.address.'.($deleted ? 'undo' : 'deleted')),
                'deleted' => $deleted,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

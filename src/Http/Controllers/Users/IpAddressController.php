<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Users;

use Exception;
use Illuminate\Http\JsonResponse;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\TableRequest;
use VanDmade\Cuztomisable\Http\Requests\Users\IpAddressSaveRequest;
use VanDmade\Cuztomisable\Services\IpAddressService;

class IpAddressController extends CuztomisableController
{

    public function __construct(
        protected readonly IpAddressService $ipAddressService
    ) {
    }

    public function get($id): JsonResponse
    {
        try {
            return $this->success([
                'ip' => $this->ipAddressService->get($id),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function table(TableRequest $request, $id = null): JsonResponse
    {
        try {
            return $this->ipAddressService->table($request->validated(), $id);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function forget($id): JsonResponse
    {
        try {
            $this->ipAddressService->forget($id);
            return $this->success([
                'message' => __('cuztomisable/user.ip_address.forgotten'),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete($id): JsonResponse
    {
        try {
            $deleted = $this->ipAddressService->toggleDelete($id);
            return $this->success([
                'message' => __('cuztomisable/user.ip_address.'.($deleted ? 'undo' : 'deleted')),
                'deleted' => $deleted,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(IpAddressSaveRequest $request, $id): JsonResponse
    {
        try {
            $ip = $this->ipAddressService->save($request->validated(), $id);
            return $this->success([
                'message' => __('cuztomisable/user.ip_address.saved'),
                'ip' => $ip,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

<?php

namespace VanDmade\Cuztomisable\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;
use VanDmade\Cuztomisable\Http\Requests\TimezoneRequest;
use VanDmade\Cuztomisable\Services\SettingsService;
use VanDmade\Cuztomisable\Services\Users\UserService;

class SettingsController extends CuztomisableController
{

    public function __construct(
        protected readonly SettingsService $settingsService,
        protected readonly UserService $userService
    ) {
    }

    public function all(): JsonResponse
    {
        try {
            return $this->success($this->settingsService->find());
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function updateTimezone(TimezoneRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $updated = $this->userService->updateTimezone(Auth::user(), $data['timezone']);
            return $this->success([
                'message' => __('cuztomisable/global.timezone.'.($updated ? 'updated' : 'unchanged')),
                'updated' => $updated,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

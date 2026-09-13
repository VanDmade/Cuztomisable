<?php

namespace VanDmade\Cuztomisable\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;
use VanDmade\Cuztomisable\Http\Requests\SettingsRequest;
use VanDmade\Cuztomisable\Http\Requests\TimezoneRequest;
use VanDmade\Cuztomisable\Services\SettingsService;
use VanDmade\Cuztomisable\Services\Users\UserService;

/**
 * Reads and writes admin-editable settings.
 */
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

    public function list(): JsonResponse
    {
        try {
            return $this->success(['settings' => $this->settingsService->list(Auth::user())]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function get(string $key): JsonResponse
    {
        try {
            if (!in_array($key, config('cuztomisable.settings', []))) {
                throw new Exception(__('cuztomisable/global.not_found'), 404);
            }
            return $this->success($this->settingsService->get($key));
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function save(SettingsRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            return $this->success([
                'message' => __('cuztomisable/global.saved'),
                'setting' => $this->settingsService->save($data['key'], $data['value']),
            ]);
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

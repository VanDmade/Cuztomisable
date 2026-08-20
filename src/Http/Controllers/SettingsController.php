<?php

namespace VanDmade\Cuztomisable\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use VanDmade\Cuztomisable\Services\SettingsService;

class SettingsController extends CuztomisableController
{

    public function __construct(
        protected readonly SettingsService $settingsService
    ) {
    }

    public function all(): JsonResponse
    {
        try {
            return $this->success($this->settingsService->get());
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

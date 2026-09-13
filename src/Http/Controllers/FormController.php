<?php

namespace VanDmade\Cuztomisable\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;
use VanDmade\Cuztomisable\Http\Requests\FormRequest;
use VanDmade\Cuztomisable\Services\FormService;

/**
 * Handles get/save for a user's in-progress multi-step form/wizard state (formerly Formora).
 */
class FormController extends CuztomisableController
{

    public function __construct(
        protected readonly FormService $formService
    ) {
    }

    public function get(string $page): JsonResponse
    {
        try {
            if (!Auth::check()) {
                throw new Exception(__('cuztomisable/global.unauthenticated'), 401);
            }
            return $this->success([
                'form' => $this->formService->find(Auth::id(), $page),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function save(FormRequest $request, string $page): JsonResponse
    {
        try {
            // Guest-accessible (see routes/api.php) - Auth::id() is null here for a guest, which
            // Form's own nullable user_id already accounts for, so someone filling out a
            // multi-step signup doesn't lose their progress just for not having an account yet.
            $data = $request->validated();
            return $this->success([
                'form' => $this->formService->save(Auth::id(), $page, $data),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

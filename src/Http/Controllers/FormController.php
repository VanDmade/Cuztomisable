<?php

namespace VanDmade\Cuztomisable\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
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
            $this->rateLimit(
                'cuztomisable:form:get:'.implode(':', [request()->ip(), $this->actorId(), $page]),
                'cuztomisable/global.server_broken'
            );
            return $this->success([
                'form' => $this->formService->get(Auth::id(), $page),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(FormRequest $request, string $page): JsonResponse
    {
        try {
            if (!Auth::check()) {
                throw new Exception(__('cuztomisable/global.unauthenticated'), 401);
            }
            $data = $request->validated();
            $this->rateLimit(
                'cuztomisable:form:save:'.implode(':', [$request->ip(), $this->actorId(), (string) ($data['current'] ?? $page)]),
                'cuztomisable/global.server_broken'
            );
            return $this->success([
                'form' => $this->formService->save(Auth::id(), $page, $data),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

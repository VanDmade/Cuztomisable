<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Users;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\Users\Passwords\ChangeRequest;
use VanDmade\Cuztomisable\Services\Users\PasswordService;

/**
 * Handles user password management operations.
 */
class PasswordController extends CuztomisableController
{

    public function __construct(
        protected readonly PasswordService $passwordService
    ) {
    }

    public function change(ChangeRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $this->passwordService->change(Auth::user(), $data);
            return $this->success([
                'message' => __('cuztomisable/user.password.changed'),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function send($id): JsonResponse
    {
        try {
            $this->passwordService->send($id);
            return $this->success([
                'message' => __('cuztomisable/user.account.temporary_password'),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

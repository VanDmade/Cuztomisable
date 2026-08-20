<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Users;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\Users\Passwords\ChangeRequest;
use VanDmade\Cuztomisable\Services\PasswordService;

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
            $this->rateLimit(
                'cuztomisable:user_passwords:change:'.implode(':', [$request->ip(), $this->actorId()]),
                'cuztomisable/user.errors.incorrect_password'
            );
            $this->passwordService->change(Auth::user(), $data);
            return $this->success([
                'message' => __('cuztomisable/user.password.changed'),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function send($id): JsonResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:user_passwords:send:'.implode(':', [$this->actorId(), (string) $id]),
                'cuztomisable/user.errors.password_changed_recently'
            );
            $this->passwordService->send($id);
            return $this->success([
                'message' => __('cuztomisable/user.account.temporary_password'),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

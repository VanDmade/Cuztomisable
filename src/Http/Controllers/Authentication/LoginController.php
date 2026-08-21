<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Authentication;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\Authentication\LoginRequest;
use VanDmade\Cuztomisable\Services\Authentication\LoginService;

/**
 * Handles user login and logout operations.
 */
class LoginController extends CuztomisableController
{

    public function __construct(
        protected readonly LoginService $loginService
    ) {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $isMobile = $request->header('X-App-Platform') === 'mobile';
            $result = $this->loginService->login($data, $isMobile);
            return $this->authResponse(
                $result['user'],
                $result,
                filter_var($data['remember'] ?? false, FILTER_VALIDATE_BOOL),
                $result['requires_mfa'],
                $result['mfa_token']
            );
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->loginService->logout($request->user());
            return $this->success([
                'message' => __('cuztomisable/authentication.login.logged_out'),
            ])->withoutCookie(config('cuztomisable.login.cookie_name', 'api_token'));
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

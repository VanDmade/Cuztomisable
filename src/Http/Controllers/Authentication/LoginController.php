<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Authentication;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\Authentication\LoginRequest;
use VanDmade\Cuztomisable\Http\Resources\AuthResource;
use VanDmade\Cuztomisable\Http\Resources\MFAResource;
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
            $remember = filter_var($data['remember'] ?? false, FILTER_VALIDATE_BOOL);
            $result = $this->loginService->login($data, $isMobile);
            if ($result['requires_mfa']) {
                return (new MFAResource($result['user'], $remember, $result['mfa_token']))->toResponse($request);
            }
            return (new AuthResource($result['user'], $result, $remember))->toResponse($request);
        } catch (Throwable $error) {
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
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

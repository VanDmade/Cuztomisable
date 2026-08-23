<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Users;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\TableRequest;
use VanDmade\Cuztomisable\Http\Requests\Users\RefreshRequest;
use VanDmade\Cuztomisable\Http\Requests\Users\UserRequest;
use VanDmade\Cuztomisable\Http\Resources\UserResource;
use VanDmade\Cuztomisable\Services\TermsService;
use VanDmade\Cuztomisable\Services\Users\UserService;

/**
 * Handles user management operations.
 */
class UserController extends CuztomisableController
{

    public function __construct(
        protected readonly UserService $userService,
        protected readonly TermsService $termsService
    ) {
    }

    public function get(Request $request, $id = null): JsonResponse
    {
        try {
            $user = $this->userService->find($request->user(), $id !== null ? (int) $id : null);
            return $this->success([
                'user' => UserResource::forUser($user),
                'change_password' => $user->change_password,
                'permissions' => $user->permissionSlugs(),
                'needs_to_accept_terms' => $this->termsService->needsToAccept($user),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function table(TableRequest $request): JsonResponse
    {
        try {
            return $this->userService->table($request->validated());
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function save(UserRequest $request, $id = null): JsonResponse
    {
        try {
            $data = $request->validated();
            $image = $request->hasFile('image') ? $request->file('image') : null;
            // Prevents creating a user within the profile
            if ($id === null && $request->routeIs('users.update')) {
                $user = $this->userService->create($data, $image);
                return $this->success([
                    'message' => __('cuztomisable/user.created'),
                    'user' => UserResource::forUser($user),
                ]);
            }
            $clearImage = !empty($data['clear_image']) && $data['clear_image'] == '1';
            $user = $this->userService->save(
                $request->user(),
                $id !== null ? (int) $id : null,
                $data,
                $image,
                $clearImage
            );
            return $this->success([
                'message' => __('cuztomisable/user.saved'),
                'user' => UserResource::forUser($user),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function toggleLocked(Request $request, $id = null): JsonResponse
    {
        try {
            $wasLocked = $this->userService->toggleLocked($request->user(), $id !== null ? (int) $id : null);
            return $this->success([
                'message' => __('cuztomisable/user.'.($wasLocked ? 'unlocked' : 'locked')),
                'locked' => $wasLocked,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete(Request $request, $id = null): JsonResponse
    {
        try {
            $deleted = $this->userService->toggleDelete($request->user(), $id !== null ? (int) $id : null);
            return $this->success([
                'message' => __('cuztomisable/user.'.($deleted ? 'undo' : 'deleted')),
                'deleted' => $deleted,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function toggleMfa(Request $request, $id = null): JsonResponse
    {
        try {
            $enabled = $this->userService->toggleMfa($request->user(), $id !== null ? (int) $id : null);
            return $this->success([
                'message' => __('cuztomisable/user.mfa.'.($enabled ? 'enabled' : 'disabled')),
                'enabled' => $enabled,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function list(): JsonResponse
    {
        try {
            return $this->success([
                'list' => $this->userService->list(),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            if (!$request->user()) {
                throw new Exception(__('cuztomisable/global.unauthenticated'), 401);
            }
            $result = $this->userService->refresh($request->user());
            return $this->success([
                'message' => __('cuztomisable/user.refresh.refreshed'),
                'token_expires_at' => $result['token_expires_at'],
            ])->withCookie($result['cookie']);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function refreshToken(RefreshRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $result = $this->userService->refreshToken($data['token']);
            return $this->success([
                'access_token' => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'expires_in' => config('cuztomisable.login.session_length', 900),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function verification(Request $request, $token, $type): JsonResponse|RedirectResponse
    {
        try {
            $verified = $this->userService->verification(
                $token,
                $type,
                $request->query('email'),
                $request->query('phone')
            );
            $messageKey = $verified ? 'verification' : 'errors.invalid_verification';
            return redirect(url('/message?m='.__('cuztomisable/user.'.$messageKey, ['type' => $type])));
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function unsubscribe(Request $request, $token, $type): JsonResponse|RedirectResponse
    {
        try {
            $unsubscribed = $this->userService->unsubscribe(
                $token,
                $type,
                $request->query('email'),
                $request->query('phone')
            );
            $messageKey = $unsubscribed ? 'unsubscribe' : 'errors.invalid_unsubscribe';
            return redirect(url('/message?m='.__('cuztomisable/user.'.$messageKey, ['type' => $type])));
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

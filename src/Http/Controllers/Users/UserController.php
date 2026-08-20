<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Users;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\TableRequest;
use VanDmade\Cuztomisable\Http\Requests\Users\RefreshRequest;
use VanDmade\Cuztomisable\Http\Requests\Users\UserRequest;
use VanDmade\Cuztomisable\Http\Resources\UserResource;
use VanDmade\Cuztomisable\Services\Users\UserService;

class UserController extends CuztomisableController
{

    public function __construct(
        protected readonly UserService $userService
    ) {
    }

    public function get(Request $request, $id = null): JsonResponse
    {
        try {
            $user = $this->userService->get($request->user(), $id !== null ? (int) $id : null);
            return $this->success([
                'user' => new UserResource($user),
                'change_password' => $user->change_password,
                'permissions' => $user->permissionSlugs(),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function table(TableRequest $request): JsonResponse
    {
        try {
            return $this->userService->table($request->validated());
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(UserRequest $request, $id = null): JsonResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:users:save:'.implode(':', [$request->ip(), $this->actorId(), (string) ($id ?? 'self')]),
                'cuztomisable/user.errors.not_found'
            );
            $data = $request->validated();
            $clearImage = !empty($data['clear_image']) && $data['clear_image'] == '1';
            $image = $request->hasFile('image') ? $request->file('image') : null;
            $user = $this->userService->save(
                $request->user(),
                $id !== null ? (int) $id : null,
                $data,
                $image,
                $clearImage
            );
            return $this->success([
                'message' => __('cuztomisable/user.saved'),
                'user' => new UserResource($user),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleLocked(Request $request, $id = null): JsonResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:users:toggle_locked:'.implode(':', [$this->actorId(), (string) ($id ?? 'self')]),
                'cuztomisable/user.errors.not_found'
            );
            $wasLocked = $this->userService->toggleLocked($request->user(), $id !== null ? (int) $id : null);
            return $this->success([
                'message' => __('cuztomisable/user.'.($wasLocked ? 'unlocked' : 'locked')),
                'locked' => $wasLocked,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete(Request $request, $id = null): JsonResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:users:toggle_delete:'.implode(':', [$this->actorId(), (string) ($id ?? 'self')]),
                'cuztomisable/user.errors.not_found'
            );
            $deleted = $this->userService->toggleDelete($request->user(), $id !== null ? (int) $id : null);
            return $this->success([
                'message' => __('cuztomisable/user.'.($deleted ? 'undo' : 'deleted')),
                'deleted' => $deleted,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleMfa(Request $request, $id = null): JsonResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:users:toggle_mfa:'.implode(':', [$this->actorId(), (string) ($id ?? 'self')]),
                'cuztomisable/user.errors.not_found'
            );
            $enabled = $this->userService->toggleMfa($request->user(), $id !== null ? (int) $id : null);
            return $this->success([
                'message' => __('cuztomisable/user.mfa.'.($enabled ? 'enabled' : 'disabled')),
                'enabled' => $enabled,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function list(): JsonResponse
    {
        try {
            return $this->success([
                'list' => $this->userService->list(),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            if (!$request->user()) {
                throw new Exception(__('cuztomisable/global.unauthenticated'), 401);
            }
            $this->rateLimit(
                'cuztomisable:users:refresh:'.implode(':', [$request->ip(), $this->actorId()]),
                'cuztomisable/global.unauthenticated',
                401
            );
            $result = $this->userService->refresh($request->user());
            return $this->success([
                'message' => __('cuztomisable/user.refresh.refreshed'),
                'token_expires_at' => $result['token_expires_at'],
            ])->withCookie($result['cookie']);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function refreshToken(RefreshRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $this->rateLimit(
                'cuztomisable:users:refresh_token:'.implode(':', [$request->ip()]),
                'cuztomisable/user.refresh.errors.not_found'
            );
            $result = $this->userService->refreshToken($data['token']);
            return $this->success([
                'access_token' => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'expires_in' => config('cuztomisable.login.session_length', 900),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    // Not currently wired into routes.php - ported for parity, same as the old controller,
    // but neither this nor unsubscribe() below has ever actually been reachable.
    public function verification(Request $request, $token, $type): JsonResponse|RedirectResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:users:verification:'.implode(':', [$request->ip(), $token, $type]),
                'cuztomisable/user.errors.not_found'
            );
            $verified = $this->userService->verification(
                $token,
                $type,
                $request->query('email'),
                $request->query('phone')
            );
            $messageKey = $verified ? 'verification' : 'errors.invalid_verification';
            return redirect(url('/message?m='.__('cuztomisable/user.'.$messageKey, ['type' => $type])));
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function unsubscribe(Request $request, $token, $type): JsonResponse|RedirectResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:users:unsubscribe:'.implode(':', [$request->ip(), $token, $type]),
                'cuztomisable/user.errors.not_found'
            );
            $unsubscribed = $this->userService->unsubscribe(
                $token,
                $type,
                $request->query('email'),
                $request->query('phone')
            );
            $messageKey = $unsubscribed ? 'unsubscribe' : 'errors.invalid_unsubscribe';
            return redirect(url('/message?m='.__('cuztomisable/user.'.$messageKey, ['type' => $type])));
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

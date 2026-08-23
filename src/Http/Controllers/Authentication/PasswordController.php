<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Authentication;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;
use VanDmade\Cuztomisable\Enums\SentVia;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\Authentication\Passwords\ForgotRequest;
use VanDmade\Cuztomisable\Http\Requests\Authentication\Passwords\ResetRequest;
use VanDmade\Cuztomisable\Services\Authentication\PasswordService;

/**
 * Handles forgot password and password reset work flows.
 */
class PasswordController extends CuztomisableController
{

    public function __construct(
        protected readonly PasswordService $passwordService
    ) {
    }

    public function forgot(ForgotRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $reset = $this->passwordService->forgot($data);
            return $this->success([
                'message' => __('cuztomisable/authentication.passwords.sent'),
                'token' => $reset->token ?? null,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function verify(Request $request, $token, $code = null): JsonResponse
    {
        try {
            $result = $this->passwordService->verify($token, $code);
            $messageKey = $result['locked'] ? 'locked' : (is_null($code) ? 'verified' : 'code_verified');
            return $this->success([
                'message' => __('cuztomisable/authentication.passwords.'.$messageKey),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function send(Request $request, $token): JsonResponse
    {
        try {
            $result = $this->passwordService->send($token);
            $reset = $result['reset'];
            $sendVia = config('cuztomisable.account.passwords.send_via');
            return $this->success([
                'message' => __('cuztomisable/authentication.passwords.resent', [
                    'sent' => $result['resending'] ? 'resent' : 'sent',
                    'location' => $reset->sent_via === SentVia::Text && $sendVia['phone'] ?
                        $reset->user->mobilePhone->obscuredNumber : $reset->user->obscuredEmail,
                ]),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function save(ResetRequest $request, $token): JsonResponse
    {
        try {
            $data = $request->validated();
            $this->passwordService->save($data, $token);
            return $this->success([
                'message' => __('cuztomisable/authentication.passwords.reset'),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function lock(Request $request, $user, $id, $token): JsonResponse|RedirectResponse
    {
        try {
            $status = $this->passwordService->lock($user, $id, $token);
            return redirect(url('message?m='.__('cuztomisable/user.account.'.$status)));
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

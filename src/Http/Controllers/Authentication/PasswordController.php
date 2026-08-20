<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Authentication;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\Authentication\Passwords\ForgotRequest;
use VanDmade\Cuztomisable\Http\Requests\Authentication\Passwords\ResetRequest;
use VanDmade\Cuztomisable\Services\Authentication\PasswordService;

/**
 * Handles forgot-password and password-reset flows.
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
            $this->rateLimit(
                'cuztomisable:passwords:forgot:'.implode(':', [$request->ip(), strtolower($data['username'])]),
                'cuztomisable/authentication.passwords.errors.already_sent'
            );
            $reset = $this->passwordService->forgot($data);
            return $this->success([
                'message' => __('cuztomisable/authentication.passwords.sent'),
                'token' => $reset->token ?? null,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function verify(Request $request, $token, $code = null): JsonResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:passwords:verify:'.implode(':', [$request->ip(), $token]),
                'cuztomisable/authentication.passwords.errors.attempt_counter'
            );
            $result = $this->passwordService->verify($token, $code);
            $messageKey = $result['locked'] ? 'locked' : (is_null($code) ? 'verified' : 'code_verified');
            return $this->success([
                'message' => __('cuztomisable/authentication.passwords.'.$messageKey),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function send(Request $request, $token): JsonResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:passwords:send:'.implode(':', [$request->ip(), $token]),
                'cuztomisable/authentication.passwords.errors.sent_recently'
            );
            $result = $this->passwordService->send($token);
            $reset = $result['reset'];
            $sendVia = config('cuztomisable.account.passwords.send_via');
            return $this->success([
                'message' => __('cuztomisable/authentication.passwords.resent', [
                    'sent' => $result['resending'] ? 'resent' : 'sent',
                    'location' => $reset->sent_via == 'phone' && $sendVia['phone'] ?
                        $reset->user->mobilePhone->obscuredNumber : $reset->user->obscuredEmail,
                ]),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(ResetRequest $request, $token): JsonResponse
    {
        try {
            $data = $request->validated();
            $this->rateLimit(
                'cuztomisable:passwords:reset:'.implode(':', [$request->ip(), $token]),
                'cuztomisable/authentication.passwords.errors.attempt_counter'
            );
            $this->passwordService->save($data, $token);
            return $this->success([
                'message' => __('cuztomisable/authentication.passwords.reset'),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function lock(Request $request, $user, $id, $token): JsonResponse|RedirectResponse
    {
        try {
            $status = $this->passwordService->lock($user, $id, $token);
            return redirect(url('message?m='.__('cuztomisable/user.account.'.$status)));
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

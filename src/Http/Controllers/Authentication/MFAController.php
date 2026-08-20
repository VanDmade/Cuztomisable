<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Authentication;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\Authentication\MFA\MFARequest;
use VanDmade\Cuztomisable\Http\Requests\Authentication\MFA\SendRequest;
use VanDmade\Cuztomisable\Services\Authentication\MfaService;

/**
 * Handles sending, verifying, and saving multi-factor authentication codes.
 */
class MFAController extends CuztomisableController
{

    public function __construct(
        protected readonly MfaService $mfaService
    ) {
    }

    public function send(SendRequest $request, $token): JsonResponse
    {
        try {
            $data = $request->validated();
            $this->rateLimit(
                'cuztomisable:mfa:send:'.implode(':', [$request->ip(), $token]),
                'cuztomisable/authentication.mfa.errors.sent_recently'
            );
            $result = $this->mfaService->send($token, $data['type']);
            $code = $result['code'];
            $sendVia = config('cuztomisable.login.multi_factor_authentication.send_via');
            return $this->success([
                'message' => __('cuztomisable/authentication.mfa.sent', [
                    'sent' => $result['resending'] ? 'resent' : 'sent',
                    'location' => $result['type'] == 'phone' && $sendVia['phone'] ?
                        $code->user->mobilePhone->obscuredNumber : $code->user->obscuredEmail,
                ]),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function verify(Request $request, $token): JsonResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:mfa:verify:'.implode(':', [$request->ip(), $token]),
                'cuztomisable/authentication.mfa.errors.not_found'
            );
            return $this->success(array_merge(
                ['message' => __('cuztomisable/authentication.mfa.verified'), 'verified' => true],
                $this->mfaService->verify($token)
            ));
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(MFARequest $request, $token): JsonResponse
    {
        try {
            $data = $request->validated();
            $this->rateLimit(
                'cuztomisable:mfa:save:'.implode(':', [$request->ip(), $token]),
                'cuztomisable/authentication.mfa.errors.not_found'
            );
            $isMobile = $request->header('X-App-Platform') === 'mobile';
            $remember = filter_var($data['remember'] ?? false, FILTER_VALIDATE_BOOL);
            $result = $this->mfaService->save($token, $data['code'], $remember, $isMobile);
            return $this->authResponse($result['user'], $result, $remember);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

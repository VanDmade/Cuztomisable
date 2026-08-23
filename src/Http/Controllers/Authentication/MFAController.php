<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Authentication;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use VanDmade\Cuztomisable\Enums\SentVia;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\Authentication\MFA\MFARequest;
use VanDmade\Cuztomisable\Http\Requests\Authentication\MFA\SendRequest;
use VanDmade\Cuztomisable\Http\Resources\AuthResource;
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
            $result = $this->mfaService->send($token, $data['type']);
            $code = $result['code'];
            $sendVia = config('cuztomisable.login.multi_factor_authentication.send_via');
            return $this->success([
                'message' => __('cuztomisable/authentication.mfa.sent', [
                    'sent' => $result['resending'] ? 'resent' : 'sent',
                    'location' => $result['type'] === SentVia::Text && $sendVia['phone'] ?
                        $code->user->mobilePhone->obscuredNumber : $code->user->obscuredEmail,
                ]),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function verify(Request $request, $token): JsonResponse
    {
        try {
            $response = $this->mfaService->verify($token);
            return $this->success(array_merge(
                [
                    'message' => __('cuztomisable/authentication.mfa.verified'),
                    'verified' => true,
                ],
                $response
            ));
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function save(MFARequest $request, $token): JsonResponse
    {
        try {
            $data = $request->validated();
            $isMobile = $request->header('X-App-Platform') === 'mobile';
            $remember = filter_var($data['remember'] ?? false, FILTER_VALIDATE_BOOL);
            $result = $this->mfaService->save($token, $data['code'], $remember, $isMobile);
            return (new AuthResource($result['user'], $result, $remember))->toResponse($request);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

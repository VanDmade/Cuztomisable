<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Authentication;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Models\Users;
use VanDmade\Cuztomisable\Http\Requests\Authentication\InviteRequest;
use VanDmade\Cuztomisable\Http\Requests\Authentication\RegistrationRequest;
use VanDmade\Cuztomisable\Http\Requests\TableRequest;
use VanDmade\Cuztomisable\Services\Authentication\RegistrationService;

/**
 * Handles user registration work flows.
 */
class RegistrationController extends CuztomisableController
{

    public function __construct(
        protected readonly RegistrationService $registrationService
    ) {
    }

    public function verify(Request $request, string $code): JsonResponse
    {
        try {
            return $this->success([
                'message' => __('cuztomisable/authentication.registration.verified'),
                'user' => $this->registrationService->verify($code),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function table(TableRequest $request): JsonResponse
    {
        try {
            return $this->registrationService->table($request->validated());
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function save(RegistrationRequest $request, $code = null): JsonResponse
    {
        try {
            $data = $request->validated();
            $this->registrationService->save($data, $code);
            $verifyEmail = config('cuztomisable.login.verification.email', false);
            $verifyPhone = config('cuztomisable.login.verification.phone', false);
            $type = $verifyEmail && $verifyPhone ? 'email address and phone number' :
                ($verifyEmail ? 'email address' : 'phone number');
            $message = ($verifyEmail || $verifyPhone) ?
                __('cuztomisable/authentication.registration.verification', ['type' => $type]) :
                __('cuztomisable/authentication.registration.created');
            return $this->success([
                'message' => $message,
                'verify' => [
                    'email' => $verifyEmail,
                    'phone' => $verifyPhone,
                ],
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function invite(InviteRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $this->registrationService->invite($data);
            return $this->success([
                'message' => __('cuztomisable/authentication.registration.invite'),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function send(Request $request, Users\Registration $registration): JsonResponse
    {
        try {
            $this->registrationService->send($registration);
            return $this->success([
                'message' => __('cuztomisable/authentication.registration.resent'),
                'resend_in' => config('cuztomisable.account.registration.resend_after', 300),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete(Users\Registration $registration): JsonResponse
    {
        try {
            $deleted = $this->registrationService->toggleDelete($registration);
            $message = __('cuztomisable/authentication.registration.'.($deleted ? 'undo' : 'deleted'));
            return $this->success([
                'message' => $message,
                'deleted' => $deleted,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

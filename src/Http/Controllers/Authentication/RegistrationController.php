<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Authentication;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Models\Users;
use VanDmade\Cuztomisable\Http\Requests\Authentication\InviteRequest;
use VanDmade\Cuztomisable\Http\Requests\Authentication\RegistrationRequest;
use VanDmade\Cuztomisable\Http\Requests\TableRequest;
use VanDmade\Cuztomisable\Services\Authentication\RegistrationService;

class RegistrationController extends CuztomisableController
{

    public function __construct(
        protected readonly RegistrationService $registrationService
    ) {
    }

    public function verify(Request $request, string $code): JsonResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:registration:verify:'.implode(':', [$request->ip(), $code]),
                'cuztomisable/authentication.registration.errors.not_found'
            );
            return $this->success([
                'message' => __('cuztomisable/authentication.registration.verified'),
                'user' => $this->registrationService->verify($code),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function table(TableRequest $request): JsonResponse
    {
        try {
            return $this->registrationService->table($request->validated());
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(RegistrationRequest $request, $code = null): JsonResponse
    {
        try {
            $data = $request->validated();
            $identifier = strtolower($data['email'] ?? ($data['phone'] ?? ''));
            $this->rateLimit(
                'cuztomisable:registration:save:'.implode(':', [$request->ip(), $identifier]),
                'cuztomisable/authentication.registration.errors.not_found'
            );
            $this->registrationService->save($data, $code);
            $verifyEmail = config('cuztomisable.login.verification.email', false);
            $verifyPhone = config('cuztomisable.login.verification.phone', false);
            $type = $verifyEmail && $verifyPhone ? 'email address and phone number' :
                ($verifyEmail ? 'email address' : 'phone number');
            $message = ($verifyEmail || $verifyPhone) ?
                __('cuztomisable/authentication.registration.verification', ['type' => $type]) :
                __('cuztomisable/authentication.registration.created');
            return $this->success(['message' => $message]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function invite(InviteRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $phone = generatePhoneNumber($data['country_code'] ?? null, $data['phone'] ?? null);
            $identifier = strtolower($data['email'] ?? $phone ?? '');
            $this->rateLimit(
                'cuztomisable:registration:invite:'.implode(':', [$request->ip(), $identifier]),
                'cuztomisable/authentication.registration.errors.recently_invited'
            );
            $this->registrationService->invite($data);
            return $this->success([
                'message' => __('cuztomisable/authentication.registration.invite'),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function send(Request $request, Users\Registration $registration): JsonResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:registration:send:'.implode(':', [$request->ip(), (string) $registration->id]),
                'cuztomisable/authentication.registration.errors.sent_recently'
            );
            $this->registrationService->send($registration);
            return $this->success([
                'message' => __('cuztomisable/authentication.registration.resent'),
                'resend_in' => config('cuztomisable.account.registration.resend_after', 300),
            ]);
        } catch (Exception $error) {
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
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

<?php

namespace VanDmade\Cuztomisable\Services\Authentication;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use VanDmade\Cuztomisable\Enums\SentVia;
use VanDmade\Cuztomisable\Jobs\SendText;
use VanDmade\Cuztomisable\Mail\Authentication\MFA as MFAMail;
use VanDmade\Cuztomisable\Services\RefreshTokenService;
use VanDmade\Cuztomisable\Services\Users\CodeService;

class MfaService
{

    public function __construct(
        protected readonly CodeService $codeService,
        protected readonly RefreshTokenService $refreshTokenService
    ) {
    }

    public function send(string $token, string $type): array
    {
        return DB::transaction(function() use ($token, $type) {
            $code = $this->codeService->findByToken($token, unusedOnly: true, unexpiredOnly: true);
            // Makes sure the code exists, hasn't been used, and a user is attached
            if (!isset($code->id)) {
                throw new Exception(__('cuztomisable/authentication.mfa.errors.not_found'), 404);
            }
            $resendAfter = config('cuztomisable.login.multi_factor_authentication.resend_after', 300);
            $resending = $code->sent_at !== null;
            // Checks to see if the code was sent recently
            if ($code->sent_at !== null && $code->sent_at->gt(now()->subSeconds($resendAfter))) {
                throw new Exception(__('cuztomisable/authentication.mfa.errors.sent_recently'), 401);
            }
            // Determines if the code needs to be recreated or not
            if (config('cuztomisable.login.multi_factor_authentication.recreate_code_on_resend', false)) {
                $code->code = generateCode(config('cuztomisable.account.code.length', 6), 'cuztomisable', $code->id);
            }
            $code->sent_at = now();
            $sendVia = config('cuztomisable.login.multi_factor_authentication.send_via');
            // A resend reuses whichever channel the code already went out on, or falls back to
            // config's phone-then-email preference; a fresh request names the channel directly
            $sentVia = $type === 'resend'
                ? ($code->sent_via ?? ($sendVia['phone'] ? SentVia::Text : SentVia::Email))
                : SentVia::from($type);
            if ($sentVia === SentVia::Text && $sendVia['phone']) {
                $phone = $code->user->mobilePhone;
                // No mobile phone on file
                if (!isset($phone->id)) {
                    throw new Exception(__('cuztomisable/authentication.mfa.errors.no_mobile_phone'), 404);
                }
                $code->sent_via = SentVia::Text;
                $message = __('cuztomisable/text.mfa', [
                    'company' => env('APP_NAME'),
                    'code' => $code->code,
                ]);
                SendText::dispatch($phone->country_code, $phone->number, $message);
            } else {
                $code->sent_via = SentVia::Email;
                Mail::to($code->user->email)->send(new MFAMail($code->user, $code));
            }
            $code->save();
            return [
                'code' => $code,
                'resending' => $resending,
                'type' => $sentVia,
            ];
        });
    }

    public function verify(string $token): array
    {
        $code = $this->codeService->findByToken($token);
        // Makes sure the code exists and hasn't been used
        if (!isset($code->id) || !is_null($code->used_at)) {
            throw new Exception(__('cuztomisable/authentication.mfa.errors.not_found'), 404);
        }
        // Makes sure the code hasn't expired
        if ($code->expires_at->isPast()) {
            throw new Exception(__('cuztomisable/authentication.mfa.errors.token_has_expired'), 401);
        }
        $sendVia = config('cuztomisable.login.multi_factor_authentication.send_via');
        $expiresIn = $code->expires_at ? now()->diffInSeconds($code->expires_at, false) : null;
        return [
            'email' => $sendVia['email'] || !$sendVia['phone'] ? $code->user->obscuredEmail : null,
            'phone' => $sendVia['phone'] ? ($code->user->mobilePhone->obscuredNumber ?? null) : null,
            'sent' => !is_null($code->sent_at),
            'sent_via' => $code->sent_via ?? null,
            'expires_in' => $expiresIn === null ? null : max(0, $expiresIn),
        ];
    }

    public function save(string $token, string $submittedCode, bool $remember, bool $isMobile): array
    {
        return DB::transaction(function() use ($token, $submittedCode, $remember, $isMobile) {
            $code = $this->codeService->findByToken($token, unusedOnly: true, unexpiredOnly: true);
            if (!isset($code->id)) {
                throw new Exception(__('cuztomisable/authentication.mfa.errors.not_found'), 404);
            }
            $maxAttempts = (int) config('cuztomisable.login.multi_factor_authentication.attempts.max', 5);
            if ($code->code !== $submittedCode) {
                $code->attempt_counter = (int) $code->attempt_counter + 1;
                $code->save();
                if ($code->attempt_counter >= $maxAttempts) {
                    $code->delete();
                    throw new Exception(__('cuztomisable/authentication.mfa.errors.attempt_counter'), 404);
                }
                throw new Exception(__('cuztomisable/authentication.mfa.errors.invalid_code'), 404);
            }
            // Determines the length of time the token will remain active
            $rememberFor = $remember || is_null(config('cuztomisable.login.session_length', null)) ?
                now()->addDays(60) : now()->addSeconds(config('cuztomisable.login.session_length', null));
            if ($remember) {
                $ipAddress = $code->ipAddress;
                if (!isset($ipAddress->id)) {
                    throw new Exception(__('cuztomisable/authentication.mfa.errors.ip_address_not_found'), 404);
                }
                $ipAddress->remember = true;
                $ipAddress->remember_until = $rememberFor;
                $ipAddress->save();
            }
            $code->used_at = now();
            $code->save();
            $code->user->codes()
                ->whereNull('used_at')
                ->where('id', '!=', $code->id)
                ->get()
                ->each
                ->delete();
            $result = ['user' => $code->user];
            if ($isMobile) {
                $result['access_token'] = $code->user->createToken('mobile')->plainTextToken;
                $result['refresh_token'] = $this->refreshTokenService->issue($code->user);
            } else {
                $result['cookie'] = $code->user->generateAuthCookie();
            }
            return $result;
        });
    }

}

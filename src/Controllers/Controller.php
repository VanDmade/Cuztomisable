<?php

namespace VanDmade\Cuztomisable\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use VanDmade\Cuztomisable\Helpers\Respondify;
use VanDmade\Cuztomisable\Services\SmsService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class Controller extends BaseController
{

    use AuthorizesRequests, ValidatesRequests;
    
    protected readonly SmsService $sms;

    public function __construct(SmsService $sms)
    {
        $this->sms = $sms;
    }

    public function success(mixed $parameters): JsonResponse
    {
        return Respondify::success($parameters);
    }

    public function error(mixed $error, array $parameters = []): JsonResponse
    {
        return Respondify::error($error, $parameters);
    }

    public function debug(mixed $parameters): JsonResponse
    {
        return Respondify::debug($parameters);
    }

    public function email(mixed $template, string $email): void
    {
        Mail::to($email)->send($template);
    }

    public function text(string $message, string $countryCode, ?string $phone = null): bool
    {
        $countryCode = ltrim(trim($countryCode), '+');
        if ($phone === null) {
            $parts = preg_split('/\s+/', $countryCode, 2);
            // Allows the phone number to be sent in as a string with the country code and separates it appropriately
            if (!empty($parts[1])) {
                $countryCode = $parts[0];
                $phone = $parts[1];
            } else {
                throw new Exception(__('cuztomisable/global.server_broken'), 500);
            }
        } else {
            $phone = trim($phone);
        }
        // Sends the text message
        return $this->sms->send($countryCode, $phone, $message);
    }

    protected function actorId(): string
    {
        return (string) (Auth::id() ?? 0);
    }

    protected function rateLimit(
        string $key,
        string $messageKey,
        int $status = 429,
        ?int $maxAttempts = null,
        ?int $decaySeconds = null,
        ?string $configKey = null
    ): void {
        if ($configKey !== null) {
            $maxAttempts = $maxAttempts ?? (int) config("cuztomisable.rate_limits.$configKey.attempts", 5);
            $decaySeconds = $decaySeconds ?? (int) config("cuztomisable.rate_limits.$configKey.decay_seconds", 60);
        }
        $maxAttempts = $maxAttempts ?? (int) config('cuztomisable.rate_limits.default.attempts', 5);
        $decaySeconds = $decaySeconds ?? (int) config('cuztomisable.rate_limits.default.decay_seconds', 60);
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw new Exception(__($messageKey), $status);
        }
        RateLimiter::hit($key, $decaySeconds);
    }

}

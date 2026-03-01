<?php

namespace VanDmade\Cuztomisable\Controllers\Authentication;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use VanDmade\Cuztomisable\Controllers\Controller;
use VanDmade\Cuztomisable\Mail\Authentication\MFA as MFAMail;
use VanDmade\Cuztomisable\Models\Personal\RefreshToken;
use VanDmade\Cuztomisable\Models\Users;
use VanDmade\Cuztomisable\Requests\Authentication\MFA\MFARequest;
use VanDmade\Cuztomisable\Requests\Authentication\MFA\SendRequest;

class MFAController extends Controller
{

    public function send(SendRequest $request, $token): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $token) {
                $data = $request->validated();
                $this->rateLimit(
                    'cuztomisable:mfa:send:'.implode(':', [$request->ip(), $token]),
                    'cuztomisable/authentication.mfa.errors.sent_recently'
                );
                $code = Users\Code::where('token', '=', $token)
                    ->whereHas('user')
                    ->whereNull('used_at')
                    ->where('expires_at', '>=', now())
                    ->first();
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
                // Checks the code to see how it was resent and resends it
                if ($data['type'] == 'resend') {
                    if (!empty($code->sent_via)) {
                        $data['type'] = $code->sent_via;
                    } else {
                        $data['type'] = $sendVia['phone'] ? 'phone' : 'email';
                    }
                }
                if ($data['type'] == 'phone' && $sendVia['phone']) {
                    $code->sent_via = 'phone';
                    $phone = $code->user->mobilePhone;
                    $message = __('cuztomisable/authentication.mfa.phone_message', [
                        'company' => env('APP_NAME'),
                        'code' => $code->code,
                    ]);
                    $this->text($message, $phone->country_code, $phone->number);
                } else {
                    $code->sent_via = 'email';
                    $this->email(new MFAMail($code->user, $code), $code->user->email);
                }
                $code->save();
                return $this->success([
                    'message' => __('cuztomisable/authentication.mfa.sent', [
                        'sent' => $resending ? 'resent' : 'sent',
                        'location' => $data['type'] == 'phone' && $sendVia['phone'] ?
                            $code->user->mobilePhone->obscuredNumber : $code->user->obscuredEmail,
                    ]),
                ]);
            });
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
            $code = Users\Code::where('token', '=', $token)
                ->whereHas('user')
                ->first();
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
            return $this->success([
                'message' => __('cuztomisable/authentication.mfa.verified'),
                'verified' => true,
                'email' => $sendVia['email'] || !$sendVia['phone'] ? $code->user->obscuredEmail : null,
                'phone' => $sendVia['phone'] ? ($code->user->mobilePhone->obscuredNumber ?? null) : null,
                'sent' => !is_null($code->sent_at),
                'sent_via' => $code->sent_via ?? null,
                'expires_in' => $expiresIn === null ? null : max(0, $expiresIn),
            ]);
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
            $response = DB::transaction(function () use ($data, $token, $request) {
                $code = Users\Code::where('token', '=', $token)
                    ->whereNull('used_at')
                    ->where('expires_at', '>=', now())
                    ->whereHas('user')
                    ->first();
                if (!isset($code->id)) {
                    throw new Exception(__('cuztomisable/authentication.mfa.errors.not_found'), 404);
                }
                $maxAttempts = (int) config('cuztomisable.login.multi_factor_authentication.attempts.max', 5);
                if ($code->code !== $data['code']) {
                    $code->attempt_counter = (int) $code->attempt_counter + 1;
                    $code->save();
                    if ($code->attempt_counter >= $maxAttempts) {
                        $code->delete();
                        throw new Exception(__('cuztomisable/authentication.mfa.errors.attempt_counter'), 404);
                    }
                    throw new Exception(__('cuztomisable/authentication.mfa.errors.invalid_code'), 404);
                }
                // Determines the length of time the token will remain active
                $rememberFor = (filter_var($data['remember'] ?? false, FILTER_VALIDATE_BOOL)) ||
                    is_null(config('cuztomisable.login.session_length', null)) ?
                        now()->addDays(60) : now()->addSeconds(config('cuztomisable.login.session_length', null));
                if (filter_var($data['remember'] ?? false, FILTER_VALIDATE_BOOL)) {
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
                $response = [
                    'message' => __('cuztomisable/authentication.login.logged_in'),
                    'multi_factor_authentication' => false,
                    'remember' => filter_var($data['remember'] ?? false, FILTER_VALIDATE_BOOL),
                    'user' => [
                        'name' => $code->user->name,
                        'email' => $code->user->email,
                        'phone' => $code->user->mobilePhone->full_phone_number ?? null,
                        'image' => !is_null($code->user->profile) ? $code->user->profile->output() : null,
                    ],
                ];
                // Determines if a mobile app is calling the authentication or not
                if ($request->header('X-App-Platform') === 'mobile') {
                    $plainRefreshToken = Str::random(64);
                    RefreshToken::create([
                        'user_id' => $code->user->id,
                        'token' => Hash::make($plainRefreshToken),
                        'expires_at' => now()->addDays(30),
                    ]);
                    $response['access_token'] = $code->user->createToken('mobile')->plainTextToken;
                    $response['refresh_token'] = $plainRefreshToken;
                    $response['expires_in'] = config('cuztomisable.login.session_length', 900);
                    return $this->success($response);
                }
                return $this->success($response)->withCookie($code->user->generateAuthCookie());
            });
            return $response;
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

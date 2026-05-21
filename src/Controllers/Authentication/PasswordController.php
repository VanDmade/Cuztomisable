<?php

namespace VanDmade\Cuztomisable\Controllers\Authentication;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use VanDmade\Cuztomisable\Controllers\Controller;
use VanDmade\Cuztomisable\Mail\Authentication\Passwords\Forgot as ForgotMail;
use VanDmade\Cuztomisable\Mail\Authentication\Passwords\Reset as ResetMail;
use VanDmade\Cuztomisable\Mail\Support as SupportMail;
use VanDmade\Cuztomisable\Models\Users;
use VanDmade\Cuztomisable\Requests\Authentication\Passwords as PasswordRequests;

class PasswordController extends Controller
{

    public function forgot(PasswordRequests\ForgotRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $data = $request->validated();
                $this->rateLimit(
                    'cuztomisable:passwords:forgot:'.implode(':', [$request->ip(), strtolower($data['username'])]),
                    'cuztomisable/authentication.passwords.errors.already_sent'
                );
                // Finds the user based on the email, username, or phone
                $user = config('auth.providers.users.model')::findUserByType($data['username'], $data['type']);
                if (!isset($user->id)) {
                    return $this->success([
                        'message' => __('cuztomisable/authentication.passwords.sent'),
                        'token' => null,
                    ]);
                }
                // Checks to see if the code was sent recently
                $timeBetweenAllowedResets = config('cuztomisable.account.passwords.time_between_allowed_resets', 900);
                $recentReset = $user->passwordResets()
                    ->where('created_at', '>', now()->subSeconds($timeBetweenAllowedResets))
                    ->whereNull('used_at')
                    ->first();
                if (isset($recentReset->id)) {
                    throw new Exception(__('cuztomisable/authentication.passwords.errors.already_sent'), 401);
                }
                $reset = Users\Passwords\Reset::create([
                    'user_id' => $user->id,
                    'sent_via' => $data['type'] == 'phone' ? 'phone' : 'email',
                ]);
                $sendVia = config('cuztomisable.account.passwords.send_via');
                if ($reset->sent_via == 'phone' && $sendVia['phone']) {
                    // TODO :: Sends the text message
                } else {
                    // Sends the email
                    $this->email(new ForgotMail($reset), $reset->user->email);
                }
                return $this->success([
                    'message' => __('cuztomisable/authentication.passwords.sent'),
                    'token' => $reset->token,
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function verify(Request $request, $token, $code = null): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $token, $code) {
                $this->rateLimit(
                    'cuztomisable:passwords:verify:'.implode(':', [$request->ip(), $token]),
                    'cuztomisable/authentication.passwords.errors.attempt_counter'
                );
                $reset = Users\Passwords\Reset::where('token', '=', $token)
                    ->whereHas('user')
                    ->whereNull('used_at')
                    ->first();
                // Makes sure the code exists, hasn't been used, and a user is attached
                if (!isset($reset->id)) {
                    throw new Exception(__('cuztomisable/authentication.passwords.errors.not_found'), 404);
                }
                // Verifies the reset code has expired
                if ($reset->expires_at->isPast()) {
                    throw new Exception(__('cuztomisable/authentication.passwords.errors.expired'), 404);
                }
                // If the code is sent in it will verify that the code is correct
                if (!is_null($code) && $reset->code != $code) {
                    // The code is not correct
                    $reset->attempt_counter = $reset->attempt_counter + 1;
                    $reset->save();
                    throw new Exception(__('cuztomisable/authentication.passwords.errors.invalid_code'), 404);
                }
                // The account is currently locked but will allow the user to reset their password still
                if ($reset->user->locked) {
                    return $this->success([
                        'message' => __('cuztomisable/authentication.passwords.locked'),
                    ]);
                }
                // Makes sure the attempt counter isn't met
                if ($reset->attempt_counter >= 5) {
                    throw new Exception(__('cuztomisable/authentication.passwords.errors.attempt_counter'), 404);
                }
                return $this->success([
                    'message' => __('cuztomisable/authentication.passwords.'.
                        (is_null($code) ? 'verified' : 'code_verified')),
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function send(Request $request, $token): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $token) {
                $this->rateLimit(
                    'cuztomisable:passwords:send:'.implode(':', [$request->ip(), $token]),
                    'cuztomisable/authentication.passwords.errors.sent_recently'
                );
                $reset = Users\Passwords\Reset::where('token', '=', $token)
                    ->whereHas('user')
                    ->whereNull('used_at')
                    ->where('expires_at', '>=', now())
                    ->first();
                // Makes sure the code exists, hasn't been used, and a user is attached
                if (!isset($reset->id)) {
                    throw new Exception(__('cuztomisable/authentication.passwords.errors.not_found'), 404);
                }
                $resendAfter = config('cuztomisable.account.passwords.resend_after', 300);
                $resending = $reset->sent_at !== null;
                // Checks to see if the code was sent recently
                if ($reset->sent_at !== null && $reset->sent_at->gt(now()->subSeconds($resendAfter))) {
                    throw new Exception(__('cuztomisable/authentication.passwords.errors.sent_recently'), 401);
                }
                // Determines if the code needs to be recreated or not
                if (config('cuztomisable.account.passwords.recreate_code_on_resend', false)) {
                    $reset->code = generateCode(config('cuztomisable.account.code.length', 6), 'cuztomisable', $reset->id);
                }
                $reset->sent_at = now();
                $reset->save();
                $sendVia = config('cuztomisable.account.passwords.send_via');
                if ($reset->sent_via == 'phone' && $sendVia['phone']) {
                    // TODO :: Sends the text message
                } else {
                    // Sends the email
                    $this->email(new ForgotMail($reset), $reset->user->email);
                }
                return $this->success([
                    'message' => __('cuztomisable/authentication.passwords.resent', [
                        'sent' => $resending ? 'resent' : 'sent',
                        'location' => $reset->sent_via == 'phone' && $sendVia['phone'] ?
                            $reset->user->mobilePhone->obscuredNumber : $reset->user->obscuredEmail,
                    ]),
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(PasswordRequests\ResetRequest $request, $token): JsonResponse
    {
        try {
            $data = $request->validated();
            $this->rateLimit(
                'cuztomisable:passwords:reset:'.implode(':', [$request->ip(), $token]),
                'cuztomisable/authentication.passwords.errors.attempt_counter'
            );
            $user = null;
            $reset = null;
            $response = DB::transaction(function () use ($data, $token, &$user, &$reset) {
                $reset = Users\Passwords\Reset::where('code', '=', $data['code'])
                    ->where('token', '=', $token)
                    ->whereNull('used_at')
                    ->where('expires_at', '>=', now())
                    ->first();
                if (!isset($reset->id)) {
                    throw new Exception(__('cuztomisable/authentication.passwords.errors.not_found'), 404);
                }
                $reset->used_at = now();
                $reset->save();
                $user = $reset->user;
                // Prevents the user from user previously used passwords
                $user->canUsePassword($data['password']);
                // Logs the password change
                Users\Passwords\Password::create([
                    'user_id' => $user->id,
                    'password' => $password = Hash::make($data['password']),
                ]);
                // Updates the user's password
                $user->password = $password;
                $user->save();
                return $this->success([
                    'message' => __('cuztomisable/authentication.passwords.reset'),
                ]);
            });
            if (config('cuztomisable.notifications.reset', false) !== false) {
                // Sends a notification to the user about the password reset occurring
                $this->email(new ResetMail($user, $reset), $user->email);
            }
            return $response;
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function lock(Request $request, $user, $id, $token): JsonResponse|RedirectResponse
    {
        try {
            return DB::transaction(function () use ($user, $id, $token) {
                $reset = Users\Passwords\Reset::where('user_id', '=', $user)
                    ->where('id', '=', $id)
                    ->where('token', '=', $token)
                    ->first();
                // Only allows a locked account to occur within the past week
                if (!isset($reset->id) || $reset->created_at->lte(now()->subWeek())) {
                    $message = __('cuztomisable/user.account.could_not_lock');
                } else {
                    $user = $reset->user;
                    if (!$user->locked) {
                        // Locks the account
                        $user->locked = true;
                        $user->save();
                        $message = __('cuztomisable/user.account.self_locked');
                        // Send admin an email that a user locked their account
                        $this->email(new SupportMail($user, $message), env('CUZTOMISABLE_ADMIN'));
                    } else {
                        // The account was already locked
                        $message = __('cuztomisable/user.account.already_locked');
                    }
                }
                return redirect(url('message?m='.$message));
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

<?php

namespace VanDmade\Cuztomisable\Controllers\Authentication;

use VanDmade\Cuztomisable\Controllers\Controller;
use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Requests\Authentication\Passwords as PasswordRequests;
use VanDmade\Cuztomisable\Mail\Authentication\Passwords\Forgot as ForgotMail;
use VanDmade\Cuztomisable\Mail\Authentication\Passwords\Reset as ResetMail;
use VanDmade\Cuztomisable\Mail\Support as SupportMail;
use VanDmade\Cuztomisable\Models\Users;
use DB;
use Exception;
use Hash;

class PasswordController extends Controller
{

    public function forgot(PasswordRequests\ForgotRequest $request)
    {
        try {
            $data = $request->validated();
            // Finds the user based on the email, username, or phone
            $user = Users\User::findUserByType($data['username'], $data['type']);
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/authentication.passwords.errors.not_found'), 404);
            }
            // Checks to see if the code was sent recently
            $timeBetweenAllowedResets = config('cuztomisable.account.passwords.time_between_allowed_resets', 900);
            $recentReset = $user->passwordResets()
                ->where('created_at', '>', date('Y-m-d H:i:s', strtotime('-'.$timeBetweenAllowedResets.' seconds')))
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
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function verify($token, $code = null)
    {
        try {
            $reset = Users\Passwords\Reset::where('token', '=', $token)
                ->whereHas('user')
                ->whereNull('used_at')
                ->first();
            // Makes sure the code exists, hasn't been used, and a user is attached
            if (!isset($reset->id)) {
                throw new Exception(__('cuztomisable/authentication.passwords.errors.not_found'), 404);
            }
            // Verifies the reset code has expired
            if (strtotime($reset->expires_at) < time()) {
                throw new Exception(__('cuztomisable/authentication.passwords.errors.expired'), 404);
            }
            // If the code is sent in it will verify that the code is correct
            if (!is_null($code) && $reset->code != $code) {
                throw new Exception(__('cuztomisable/authentication.passwords.errors.invalid_code'), 404);
            }
            // The account is currently locked but will allow the user to reset their password still
            if ($reset->user->locked) {
                return $this->success([
                    'message' => __('cuztomisable/authentication.passwords.locked'),
                ]);
            }
            return $this->success([
                'message' => __('cuztomisable/authentication.passwords.'.
                    (is_null($code) ? 'verified' : 'code_verified')),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function send($token)
    {
        try {
            $reset = Users\Passwords\Reset::where('token', '=', $token)
                ->whereHas('user')
                ->whereNull('used_at')
                ->where('expires_at', '>=', date('Y-m-d H:i:s'))
                ->first();
            // Makes sure the code exists, hasn't been used, and a user is attached
            if (!isset($reset->id)) {
                throw new Exception(__('cuztomisable/authentication.passwords.errors.not_found'), 404);
            }
            $resendAfter = config('cuztomisable.account.passwords.resend_after', 300);
            $resending = is_null($reset->sent_at) ? false : true;
            // Checks to see if the code was sent recently
            if (!is_null($reset->sent_at) && strtotime('-'.$resendAfter.' seconds') < strtotime($reset->sent_at)) {
                throw new Exception(__('cuztomisable/authentication.passwords.errors.sent_recently'), 401);
            }
            // Determines if the code needs to be recreated or not
            if (config('cuztomisable.account.passwords.recreate_code_on_resend', false)) {
                $reset->code = generateCode(config('cuztomisable.account.code.length', 6), 'cuztomisable', $reset->id);
            }
            $reset->sent_at = date('Y-m-d H:i:s');
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
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(PasswordRequests\ResetRequest $request, $token)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            $reset = Users\Passwords\Reset::where('code', '=', $data['code'])
                ->where('token', '=', $token)
                ->whereNull('used_at')
                ->where('expires_at', '>=', date('Y-m-d H:i:s'))
                ->first();
            if (!isset($reset->id)) {
                throw new Exception(__('cuztomisable/authentication.passwords.errors.not_found'), 404);
            }
            $reset->used_at = date('Y-m-d H:i:s');
            $reset->save();
            $user = $reset->user;
            if (!is_null($reuseAfter = config('cuztomisable.account.passwords.reuse_after', 3))) {
                $passwords = $user->passwords()
                    ->orderBy('id', 'desc')
                    ->limit(config('cuztomisable.account.passwords.reuse_after', 3))
                    ->get();
                foreach ($passwords as $i => $password) {
                    if (Hash::check($data['password'], $password->password)) {
                        throw new Exception(__('cuztomisable/authentication.passwords.errors.used_recently'), 404);
                    }
                }
            }
            // Logs the password change
            Users\Passwords\Password::create([
                'user_id' => $user->id,
                'password' => $password = Hash::make($data['password']),
            ]);
            // Updates the user's password
            $user->password = $password;
            $user->save();
            DB::commit();
            if (config('cuztomisable.account.notifications.reset', false) !== false) {
                // Sends a notification to the user about the password reset occurring
                $this->email(new ResetMail($user, $reset), $user->email);
            }
            return $this->success([
                'message' => __('cuztomisable/authentication.passwords.reset'),
            ]);
        } catch (Exception $error) {
            DB::rollback();
            return $this->error($error);
        }
    }

    public function lock($user, $id, $token)
    {
        try {
            DB::beginTransaction();
            $reset = Users\Passwords\Reset::where('user_id', '=', $user)
                ->where('id', '=', $id)
                ->where('token', '=', $token)
                ->first();
            // Only allows a locked account to occur within the past week
            if (!isset($reset->id) || strtotime($reset->created_at) <= strtotime('-1 week')) {
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
            DB::commit();
            return redirect(url('message?m='.$message));
        } catch (Exception $error) {
            DB::rollback();
            return $this->error($error);
        }
    }

}

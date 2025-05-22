<?php

namespace VanDmade\Cuztomisable\Controllers\Authentication;

use VanDmade\Cuztomisable\Controllers\Controller;
use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Requests\Authentication\LoginRequest;
use VanDmade\Cuztomisable\Models\Users;
use Auth;
use Carbon\Carbon;
use DB;
use Exception;
use Hash;

class LoginController extends Controller
{

    public function login(LoginRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            // Finds the user based on the email, username, or phone
            $user = Users\User::findUserByType($data['username'], $data['type']);
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/authentication.login.errors.invalid_credentials'), 404);
            }
            // Verifies the username / password combination
            if (!Auth::attemptWhen([
                'email' => $user->email,
                'password' => $data['password'],
            ], function (Users\User $user) {
                // Checks to see if the user can log into their account
                return $user->canLogIn();
            })) {
                $user->attempts++;
                if ($user->attempts >= config('cuztomisable.login.attempts.total', 5)) {
                    $user->attempts = 0;
                    if (config('cuztomisable.login.attempts.locked', false)) {
                        $user->locked = true;
                    } else {
                        $user->attempt_timer = date(
                            'Y-m-d H:i:s',
                            strtotime('+'.config('cuztomisable.login.attempts.timer', 300).' seconds')
                        );
                    }
                }
                if (!is_null($user->attempt_timer) && strtotime($user->attempt_timer) > time()) {
                    $user->attempts = 0;
                    $user->save();
                    throw new Exception(__('cuztomisable/authentication.login.errors.attempts'), 401);
                }
                $user->save();
                // The credentials do not match
                throw new Exception(__('cuztomisable/authentication.login.errors.invalid_credentials'), 401);
            }
            // Store IP Address to mark that the user has access to the account based on username/password
            $ipAddress = $user->ipAddresses()->where('ip_address', '=', getIpAddress())->first();
            if (!isset($ipAddress)) {
                $ipAddress = new Users\IpAddress();
                $ipAddress->user_id = $user->id;
            }
            $ipAddress->last_used_at = date('Y-m-d H:i:s');
            $ipAddress->save();
            if ($ipAddress->requireMfa()) {
                // Disables all other MFA accounts
                $user->codes()->whereNull('used_at')
                    ->update([
                        'deleted_at' => date('Y-m-d H:i:s'),
                        'deleted_by' => Auth::user()->id,
                    ]);
                // Gets the token for the authentication
                $userCode = Users\Code::create([
                    'user_id' => $user->id,
                    'user_ip_address_id' => $ipAddress->id,
                ]);
                if (!isset($userCode->id)) {
                    throw new Exception(__('cuztomisable/authentication.mfa.errors.not_created'), 500);
                }
                $token = $userCode->token;
            } else {
                if (false) {
                    // Removes all older tokens for this specific user and IP Address
                    $user->tokens()
                        ->where('name', '=', $tokenName = $user->id.'-'.$ipAddress->id.'-token')
                        ->delete();
                }
                // Determines the length of time the token will remain active
                $rememberFor = (isset($data['remember']) && $data['remember'] == '1') ||
                    is_null(config('cuztomisable.login.session_length', null)) ?
                        now()->addDays(60) : now()->addSeconds(config('cuztomisable.login.session_length', null));
                $token = $user->createToken($tokenName ?? 'access-token');
                $token->accessToken->expires_at = $rememberFor;
                $token->accessToken->save();
                $token = $token->plainTextToken;
            }
            // Unsets the attempts / timer
            $user->attempts = 0;
            $user->attempt_timer = null;
            $user->save();
            DB::commit();
            $response = $this->success([
                'message' => __('cuztomisable/authentication.login.'.($ipAddress->requireMfa() ? 'mfa_' : '').'logged_in'),
                'token' => $token ?? null,
                'multi_factor_authentication' => $ipAddress->requireMfa(),
                'remember' => isset($data['remember']) && $data['remember'] == '1',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => !is_null($user->mobilePhone) ? $user->mobilePhone->full_phone_number : null,
                    'image' => !is_null($user->profile) ? $user->profile->output() : null,
                ],
            ]);
            // Determines if the use actually logged in and wasn't sent to MFA
            if (!$ipAddress->requireMfa()) {
                $response->withCookie(
                    cookie(
                        'api_token',
                        $token,
                        60,           // minutes
                        '/',
                        null,
                        true,         // Secure
                        true,         // HttpOnly
                        false,        // raw
                        'Strict'      // SameSite
                    )
                );
            }
            return $response;
        } catch (Exception $error) {
            DB::rollback();
            return $this->error($error);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            // Revoke current access token (from cookie-injected Authorization header)
            $user?->currentAccessToken()?->delete();
            // Return response and delete the cookie
            return $this->success([
                'message' => __('cuztomisable/authentication.login.logged_out'),
            ])->withoutCookie('api_token');
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

<?php

namespace VanDmade\Cuztomisable\Controllers\Authentication;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use VanDmade\Cuztomisable\Controllers\Controller;
use VanDmade\Cuztomisable\Models\Personal\RefreshToken;
use VanDmade\Cuztomisable\Models\Users;
use VanDmade\Cuztomisable\Requests\Authentication\LoginRequest;

class LoginController extends Controller
{

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $data = $request->validated();
                // Finds the user based on the email, username, or phone
                $user = config('auth.providers.users.model')::findUserByType($data['username'], $data['type']);
                if (!isset($user->id)) {
                    throw new Exception(__('cuztomisable/authentication.login.errors.invalid_credentials'), 401);
                }
                // Verifies the username / password combination without logging in
                if (!Auth::validate([
                    'email' => $user->email,
                    'password' => $data['password'],
                ])) {
                    $user->addAttempt();
                    throw new Exception(__('cuztomisable/authentication.login.errors.invalid_credentials'), 401);
                }
                $user->canLogIn();
                // Store IP Address to mark that the user has access to the account based on username/password
                $ipAddress = $user->ipAddresses()->where('ip_address', '=', getIpAddress())->first();
                if (!isset($ipAddress)) {
                    $ipAddress = new Users\IpAddress();
                    $ipAddress->user_id = $user->id;
                }
                $ipAddress->last_used_at = now();
                $ipAddress->save();
                if ($ipAddress->requireMfa()) {
                    // Disables all other MFA accounts
                    $user->codes()
                        ->whereNull('used_at')
                        ->get()
                        ->each
                        ->delete();
                    // Gets the token for the authentication
                    $userCode = Users\Code::create([
                        'user_id' => $user->id,
                        'user_ip_address_id' => $ipAddress->id,
                    ]);
                    if (!isset($userCode->id)) {
                        throw new Exception(__('cuztomisable/authentication.mfa.errors.not_created'), 500);
                    }
                    $token = $userCode->token;
                }
                // Unsets the attempts / timer
                $user->attempts = 0;
                $user->attempt_timer = null;
                $user->save();
                $response = [
                    'message' => __('cuztomisable/authentication.login.'.($ipAddress->requireMfa() ? 'mfa_' : '').'logged_in'),
                    'token' => $token ?? null,
                    'multi_factor_authentication' => $ipAddress->requireMfa(),
                    'remember' => filter_var($data['remember'] ?? false, FILTER_VALIDATE_BOOL),
                    'user' => [
                        'id' => $user->id,
                        'admin' => $user->admin,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->defaultPhone,
                        'address' => $user->defaultAddress,
                        'mfa' => $user->multi_factor_authentication ?? false,
                        'image' => !is_null($user->profile) ? $user->profile->output() : null,
                    ],
                    'permissions' => $user->permissionSlugs(),
                    'change_password' => $user->change_password,
                ];
                // Determines if a mobile app is calling the authentication or not
                if ($request->header('X-App-Platform') === 'mobile' && !$ipAddress->requireMfa()) {
                    $plainRefreshToken = Str::random(64);
                    RefreshToken::create([
                        'user_id' => $user->id,
                        'token' => Hash::make($plainRefreshToken),
                        'expires_at' => now()->addDays(30),
                    ]);
                    $response['access_token'] = $user->createToken('mobile')->plainTextToken;
                    $response['refresh_token'] = $plainRefreshToken;
                    $response['expires_in'] = config('cuztomisable.login.session_length', 900);
                    return $this->success($response);
                }
                $response = $this->success($response);
                // Attaches the server cookie to the response
                return !$ipAddress->requireMfa() ?
                    $response->withCookie($user->generateAuthCookie()) : $response;
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            // Revoke current access token (from cookie-injected Authorization header)
            $user?->currentAccessToken()?->delete();
            // Return response and delete the cookie
            return $this->success([
                'message' => __('cuztomisable/authentication.login.logged_out'),
            ])->withoutCookie(config('cuztomisable.login.cookie_name', 'api_token'));
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

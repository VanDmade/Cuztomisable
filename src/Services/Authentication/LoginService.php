<?php

namespace VanDmade\Cuztomisable\Services\Authentication;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use VanDmade\Cuztomisable\Models\Personal\RefreshToken;
use VanDmade\Cuztomisable\Models\Users;

class LoginService
{

    public function login(array $data, bool $isMobile): array
    {
        return DB::transaction(function() use ($data, $isMobile) {
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
            $requiresMfa = $ipAddress->requireMfa();
            $mfaToken = null;
            if ($requiresMfa) {
                // Disables all other pending MFA codes
                $user->codes()
                    ->whereNull('used_at')
                    ->get()
                    ->each
                    ->delete();
                $userCode = Users\Code::create([
                    'user_id' => $user->id,
                    'user_ip_address_id' => $ipAddress->id,
                ]);
                if (!isset($userCode->id)) {
                    throw new Exception(__('cuztomisable/authentication.mfa.errors.not_created'), 500);
                }
                $mfaToken = $userCode->token;
            }
            // Unsets the attempts / timer
            $user->attempts = 0;
            $user->attempt_timer = null;
            $user->save();
            $result = [
                'user' => $user,
                'requires_mfa' => $requiresMfa,
                'mfa_token' => $mfaToken,
            ];
            // Determines if a mobile app is calling the authentication or not
            if ($isMobile && !$requiresMfa) {
                $plainRefreshToken = Str::random(64);
                RefreshToken::create([
                    'user_id' => $user->id,
                    'token' => Hash::make($plainRefreshToken),
                    'expires_at' => now()->addDays(30),
                ]);
                $result['access_token'] = $user->createToken('mobile')->plainTextToken;
                $result['refresh_token'] = $plainRefreshToken;
            } elseif (!$requiresMfa) {
                $result['cookie'] = $user->generateAuthCookie();
            }
            return $result;
        });
    }

    public function logout($user): void
    {
        // Revoke current access token (from cookie-injected Authorization header)
        $user?->currentAccessToken()?->delete();
    }

}

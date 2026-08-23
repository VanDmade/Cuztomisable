<?php

namespace VanDmade\Cuztomisable\Services\Authentication;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use VanDmade\Cuztomisable\Enums\SentVia;
use VanDmade\Cuztomisable\Events\PasswordReset as PasswordResetEvent;
use VanDmade\Cuztomisable\Jobs\SendText;
use VanDmade\Cuztomisable\Mail\Authentication\Passwords\Forgot as ForgotMail;
use VanDmade\Cuztomisable\Mail\Authentication\Passwords\Reset as ResetMail;
use VanDmade\Cuztomisable\Mail\Support as SupportMail;
use VanDmade\Cuztomisable\Models\Users;

class PasswordService
{

    private function findActiveByToken(string $token): Users\Passwords\Reset
    {
        $reset = Users\Passwords\Reset::where('token', '=', $token)
            ->whereHas('user')
            ->whereNull('used_at')
            ->first();
        if (!isset($reset->id)) {
            throw new Exception(__('cuztomisable/authentication.passwords.errors.not_found'), 404);
        }
        return $reset;
    }

    public function forgot(array $data): ?Users\Passwords\Reset
    {
        return DB::transaction(function() use ($data) {
            // Finds the user based on the email, username, or phone
            $user = config('auth.providers.users.model')::findUserByType($data['username'], $data['type']);
            if (!isset($user->id)) {
                return null;
            }
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
                'sent_via' => $data['type'] == 'phone' ? SentVia::Text : SentVia::Email,
            ]);
            $this->deliver($reset);
            return $reset;
        });
    }

    public function send(string $token): array
    {
        return DB::transaction(function() use ($token) {
            $reset = Users\Passwords\Reset::where('token', '=', $token)
                ->whereHas('user')
                ->whereNull('used_at')
                ->where('expires_at', '>=', now())
                ->first();
            if (!isset($reset->id)) {
                throw new Exception(__('cuztomisable/authentication.passwords.errors.not_found'), 404);
            }
            $resendAfter = config('cuztomisable.account.passwords.resend_after', 300);
            $resending = $reset->sent_at !== null;
            // Checks to see if the code was sent recently
            if ($reset->sent_at !== null && $reset->sent_at->gt(now()->subSeconds($resendAfter))) {
                throw new Exception(__('cuztomisable/authentication.passwords.errors.sent_recently'), 401);
            }
            if (config('cuztomisable.account.passwords.recreate_code_on_resend', false)) {
                // This prevents them guessing incorrect codes over nad over and allows for a new code each time. (CAn get confusing)
                $reset->code = Users\Passwords\Reset::generateNumericCode((int) config('cuztomisable.account.code.length', 6));
            }
            $reset->sent_at = now();
            $reset->save();
            $this->deliver($reset);
            return [
                'reset' => $reset,
                'resending' => $resending,
            ];
        });
    }

    public function verify(string $token, ?string $code): array
    {
        $reset = $this->findActiveByToken($token);
        if ($reset->expires_at->isPast()) {
            throw new Exception(__('cuztomisable/authentication.passwords.errors.expired'), 404);
        }
        if (!is_null($code) && $reset->code !== $code) {
            $reset->attempt_counter = $reset->attempt_counter + 1;
            $reset->save();
            throw new Exception(__('cuztomisable/authentication.passwords.errors.invalid_code'), 404);
        }
        // The account is currently locked but the user is still allowed to reset their password
        if ($reset->user->locked) {
            return ['locked' => true];
        }
        if ($reset->attempt_counter >= 5) {
            throw new Exception(__('cuztomisable/authentication.passwords.errors.attempt_counter'), 404);
        }
        return ['locked' => false];
    }

    public function save(array $data, string $token): array
    {
        $user = null;
        $reset = null;
        DB::transaction(function() use ($data, $token, &$user, &$reset) {
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
            // Prevents the user from using a previously used password
            $user->canUsePassword($data['password']);
            Users\Passwords\Password::create([
                'user_id' => $user->id,
                'password' => $password = Hash::make($data['password']),
            ]);
            $user->password = $password;
            $user->save();
        });
        if (config('cuztomisable.notifications.reset.enabled', true)) {
            // Notifies the user their password was reset
            Mail::to($user->email)->send(new ResetMail($user, $reset));
        }
        // Package-owned hook for host apps
        PasswordResetEvent::dispatch($user, $reset);
        return ['user' => $user, 'reset' => $reset];
    }

    public function lock($userId, $id, string $token): string
    {
        // This allows each system to determine IF they want to allow a user to lock their account without logging in
        if (!config('cuztomisable.account.passwords.allow_lock_via_token', false)) {
            return 'could_not_lock';
        }
        return DB::transaction(function() use ($userId, $id, $token) {
            $reset = Users\Passwords\Reset::where('user_id', '=', $userId)
                ->where('id', '=', $id)
                ->where('token', '=', $token)
                ->first();
            $lockWindow = config('cuztomisable.account.passwords.lock_window_seconds', 86400);
            // Only allows a locked account to occur within the configured window of the reset
            if (!isset($reset->id) || $reset->created_at->lte(now()->subSeconds($lockWindow))) {
                return 'could_not_lock';
            }
            $user = $reset->user;
            if ($user->locked) {
                return 'already_locked';
            }
            $user->locked = true;
            $user->save();
            // Sends admin an email that a user locked their own account
            Mail::to(env('CUZTOMISABLE_ADMIN'))->send(
                new SupportMail($user, __('cuztomisable/user.account.self_locked'))
            );
            return 'self_locked';
        });
    }

    private function deliver(Users\Passwords\Reset $reset): void
    {
        // This method is used to determine how the rest code is sent
        $sendVia = config('cuztomisable.account.passwords.send_via');
        if ($reset->sent_via === SentVia::Text && $sendVia['phone']) {
            $phone = $reset->user->mobilePhone;
            if (isset($phone->id)) {
                $message = __('cuztomisable/text.passwords.reset', [
                    'company' => env('APP_NAME'),
                    'url' => url('/password/forgot/'.$reset->token),
                ]);
                SendText::dispatch($phone->country_code, $phone->number, $message);
            }
        } else {
            Mail::to($reset->user->email)->send(new ForgotMail($reset));
        }
    }

}

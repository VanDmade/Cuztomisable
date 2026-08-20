<?php

namespace VanDmade\Cuztomisable\Services;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use VanDmade\Cuztomisable\Mail\Users\Passwords\Changed as ChangedMail;
use VanDmade\Cuztomisable\Mail\Users\Passwords\Temporary as TemporaryMail;
use VanDmade\Cuztomisable\Models\Users;

/**
 * Orchestration for an authenticated user changing their own password, and an admin sending
 * a temporary password to another user - the forgot/reset (unauthenticated) flow lives in
 * Services\Authentication\PasswordService instead.
 */
class PasswordService
{

    public function change(Model $user, array $data): void
    {
        DB::transaction(function() use ($user, $data) {
            $cooldown = (int) config('cuztomisable.account.passwords.change_cooldown_seconds', 0);
            if ($cooldown > 0) {
                $lastPassword = $user->passwords()->latest()->first();
                if ($lastPassword && $lastPassword->created_at->gt(now()->subSeconds($cooldown))) {
                    throw new Exception(__('cuztomisable/user.errors.password_changed_recently'), 403);
                }
            }
            // Determines if the user is doing a forced password change or just altering it themselves
            if (!isset($data['force']) || $data['force'] === false) {
                // Checks that the user can change their password. Otherwise, it'll error
                $user->canChangePassword();
                // Validate the current password
                if (!Hash::check($data['current'], $user->password)) {
                    $user->addAttempt();
                    throw new Exception(__('cuztomisable/user.errors.incorrect_password'), 404);
                }
            } elseif (!$user->change_password) {
                // Checks to make sure that the user is set up to change their password by force
                throw new Exception(__('cuztomisable/user.errors.no_force_change_allowed'), 403);
            }
            // Prevents the user from reusing a previously used password
            $user->canUsePassword($data['new']);
            Users\Passwords\Password::create([
                'user_id' => $user->id,
                'password' => $password = Hash::make($data['new']),
            ]);
            $user->password = $password;
            $user->change_password = false;
            $user->change_password_sent_at = null;
            $user->save();
            if (filter_var($data['invalidate_sessions'] ?? false, FILTER_VALIDATE_BOOL)) {
                $currentTokenId = $user->currentAccessToken()?->id;
                $tokens = $user->tokens();
                if ($currentTokenId) {
                    $tokens->where('id', '!=', $currentTokenId);
                }
                $tokens->delete();
            }
            // Sends the notification email about the password change
            Mail::to($user->email)->send(new ChangedMail($user));
        });
    }

    public function send($id): void
    {
        DB::transaction(function() use ($id) {
            $user = config('auth.providers.users.model')::where('id', '=', $id)->first();
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
            }
            // Makes sure the administrator is not spamming anyone
            $resend = config('cuztomisable.account.administrator.temporary_password.resend_after', 300);
            if (!is_null($resend) && $user->change_password_sent_at !== null &&
                $user->change_password_sent_at->gt(now()->subSeconds($resend))) {
                throw new Exception(__('cuztomisable/user.errors.password_changed_recently'), 404);
            }
            $user->password = Hash::make($password = generateCode(8));
            $user->change_password = true;
            $user->change_password_sent_at = now();
            $user->save();
            Mail::to($user->email)->send(new TemporaryMail($user, $password));
        });
    }

}

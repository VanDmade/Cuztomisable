<?php

namespace VanDmade\Cuztomisable\Controllers\Users;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use VanDmade\Cuztomisable\Controllers\Controller;
use VanDmade\Cuztomisable\Mail\Users\Passwords\Changed as ChangedMail;
use VanDmade\Cuztomisable\Mail\Users\Passwords\Temporary as TemporaryMail;
use VanDmade\Cuztomisable\Models\Users as UserModels;
use VanDmade\Cuztomisable\Requests\Users\Passwords\ChangeRequest;

class PasswordController extends Controller
{

    public function change(ChangeRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $data = $request->validated();
                $this->rateLimit(
                    'cuztomisable:user_passwords:change:'.implode(':', [$request->ip(), $this->actorId()]),
                    'cuztomisable/user.errors.incorrect_password'
                );
                $user = Auth::user();
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
                // Prevents the user from user previously used passwords
                $user->canUsePassword($data['new']);
                // Logs the password change
                UserModels\Passwords\Password::create([
                    'user_id' => $user->id,
                    'password' => $password = Hash::make($data['new']),
                ]);
                // Updates the user's password
                $user->password = $password;
                // Clears the forced change
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
                $this->email(new ChangedMail($user), $user->email);
                return $this->success([
                    'message' => __('cuztomisable/user.password.changed') ?? '',
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function send($id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                $this->rateLimit(
                    'cuztomisable:user_passwords:send:'.implode(':', [$this->actorId(), (string) $id]),
                    'cuztomisable/user.errors.password_changed_recently'
                );
                $user = config('auth.providers.users.model')::where('id', '=', $id)->first();
                if (!isset($user->id)) {
                    throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
                }
                // Makes sure the administrator is not spaming anyone
                $resend = config('cuztomisable.account.administrator.temporary_password.resend_after', 300);
                if (!is_null($resend) && $user->change_password_sent_at !== null &&
                    $user->change_password_sent_at->gt(now()->subSeconds($resend))) {
                    throw new Exception(__('cuztomisable/user.errors.password_changed_recently'), 404);
                }
                $user->password = Hash::make($password = generateCode(8));
                $user->change_password = true;
                $user->change_password_sent_at = now();
                $user->save();
                // Sends the email
                $this->email(new TemporaryMail($user, $password), $user->email);
                return $this->success([
                    'message' => __('cuztomisable/user.account.temporary_password'),
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

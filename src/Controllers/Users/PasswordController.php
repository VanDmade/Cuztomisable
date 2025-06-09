<?php

namespace VanDmade\Cuztomisable\Controllers\Users;

use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Controllers\Controller;
use VanDmade\Cuztomisable\Requests\Users\Passwords\ChangeRequest;
use VanDmade\Cuztomisable\Models\Users;
use VanDmade\Cuztomisable\Mail\Users\Passwords\Temporary as TemporaryMail;
use VanDmade\Cuztomisable\Mail\Users\Passwords\Changed as ChangedMail;
use Auth;
use Exception;
use Hash;

class PasswordController extends Controller
{

    public function change(ChangeRequest $request)
    {
        try {
            $data = $request->validated();
            $user = Auth::user();
            // Checks that the user can change their password. Otherwise, it'll error
            $user->canChangePassword();
            // Validate the current password
            if (!Hash::check($data['current'], $user->password)) {
                $user->addAttempt();
                throw new Exception(__('cuztomisable.user.errors.incorrect_password'), 404);
            }
            // Prevents the user from user previously used passwords
            $user->canUsePassword($data['new']);
            // Logs the password change
            Users\Passwords\Password::create([
                'user_id' => $user->id,
                'password' => $password = Hash::make($data['new']),
            ]);
            // Updates the user's password
            $user->password = $password;
            $user->save();
            // Sends the notification email about the password change
            $this->email(new ChangedMail($user), $user->email);
            return $this->success([
                'message' => $message ?? '',
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function send($id)
    {
        try {
            $user = Users\User::where('id', '=', $id)->first();
            if (!isset($user->id)) {
                throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
            }
            // Makes sure the administrator is not spaming anyone
            $resend = config('cuztomisable.account.administrator.temporary_password.resend_after', 300);
            if (!is_null($resend) && strtotime($user->change_password_sent_at) > strtotime('-'.$resend.' seconds')) {
                throw new Exception(__('cuztomisable/user.errors.password_changed_recently'), 404);
            }
            $user->password = Hash::make($password = generateCode(8));
            $user->change_password = true;
            $user->change_password_sent_at = date('Y-m-d H:i:s');
            $user->save();
            // Sends the email
            $this->email(new TemporaryMail($user, $password), $user->email);
            return $this->success([
                'message' => __('cuztomisable/user.account.temporary_password'),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

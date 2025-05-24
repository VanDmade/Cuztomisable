<?php

namespace VanDmade\Cuztomisable\Controllers\Authentication;

use VanDmade\Cuztomisable\Controllers\Controller;
use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Requests\Authentication\RegistrationRequest;
use VanDmade\Cuztomisable\Mail\Users\Verification as VerificationMail;
use VanDmade\Cuztomisable\Mail\Users\Registered as RegisteredMail;
use VanDmade\Cuztomisable\Models\Address;
use VanDmade\Cuztomisable\Models\Phone;
use VanDmade\Cuztomisable\Models\Users;
use DB;
use Exception;
use Hash;

class RegistrationController extends Controller
{

    public function verify($code)
    {
        try {
            $registration = Registration::where('code', '=', $code)->first();
            if (!isset($registration->id)) {
                throw new Exception(__('cuztomisable/authentication.registration.errors.not_found'), 404);
            }
            if (!is_null($registration->used_at)) {
                throw new Exception(__('cuztomisable/authentication.registration.errors.used'), 401);
            }
            if (!is_null($registration->expires_at) && strtotime($registration->expires_at) < time()) {
                throw new Exception(__('cuztomisable/authentication.registration.errors.expired'), 403);
            }
            return $this->success([
                'message' => __('cuztomisable/authentication.registration.verified'),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(RegistrationRequest $request, $code = null)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            // Creates the user
            $user = Users\User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username'] ?? null,
                'password' => $password = Hash::make($data['password']),
                'timezone' => $data['timezone'] ?? 'EST',
            ]);
            // Creates the instance of a password so the user cannot use the password again
            Users\Passwords\Password::create([
                'password' => $password,
                'user_id' => $user->id,
            ]);
            if (!is_null($code)) {
                // Gets the registration to be updated with the new user's inforamtion
                $registration = Users\Registration::where('code', '=', $code)
                    ->whereNull('used_at')
                    ->where(function($query) {
                        $query->orWhereNull('expires_at')
                            ->orWhere('expires_at', '>=', date('Y-m-d H:i:s'));
                    })
                    ->first();
                if (!isset($registration->id)) {
                    throw new Exception(__('cuztomisable/authentication.registration.errors.not_found'), 404);
                }
                $registration->user_id = $user->id;
                $registration->used_at = date('Y-m-d H:i:s');
                $registration->save();
                $user->created_by = $registration->created_by;
                $user->save();
                // The creator will receive an email of the registration
                $sendRegisteredTo = $registration->createdBy->email ?? null;
            } elseif (filter_var($email = env('CUZTOMISABLE_ADMIN', null), FILTER_VALIDATE_EMAIL)) {
                // The main administrator of the site will receive an email of the registration
                $sendRegisteredTo = $email;
            }
            // Determines if the phone is set up and entered
            if (isset($data['phone']) && $data['phone'] != '') {
                $phone = Phone::create([
                    'user_id' => $user->id,
                    'number' => $data['phone'],
                    'country_code' => $data['country_code'] ?? 1,
                    'default' => true,
                ]);
            }
            // Makes sure the address is entered or if it needs to be ignored
            if (config('cuztomisable.account.registration.address') !== false &&
                isset($data['address']) && $data['address'] != '') {
                Address::create([
                    'user_id' => $user->id,
                    'address' => $data['address'],
                    'address_two' => $data['address_two'] ?? null,
                    'address_three' => $data['address_three'] ?? null,
                    'state_or_province' => $data['state_or_province'],
                    'city' => $data['city'],
                    'country' => $data['country'],
                    'zip_or_postal_code' => $data['zip_or_postal_code'],
                    'default' => true,
                ]);
            }
            // Creates the phone entry for the user
            if (config('cuztomisable.account.notifications.email_verification', false) !== false) {
                // Sends the email verification message to the user
                $this->email(new VerificationMail($user), $user->email);
            }
            if (isset($phone->id) &&
                config('cuztomisable.authentication.notifications.phone_verification', false) !== false) {
                // TODO :: Sends the phone verification text to the user
            }
            // Determines if the admin or creator should be notified about the recent registration
            if (!is_null($sendRegisteredTo) && config('cuztomisable.account.registration.send_notification', false)) {
                // Send a notification to the creator of the invitation
                $this->email(new RegisteredMail($user), $user->email);
            }
            DB::commit();
            $verifyEmail = config('cuztomisable.login.verification.email', false);
            $verifyPhone = config('cuztomisable.login.verification.phone', false);
            if ($verifyEmail || $verifyPhone) {
                $message = __('cuztomisable/authentication.registration.verification', [
                    'type' => $verifyEmail && $verifyPhone ? 'email address and phone number' :
                        ($verifyEmail ? 'email address' : 'phone number'),
                ]);
            }
            return $this->success([
                'message' => $message ?? __('cuztomisable/authentication.registration.created'),
            ]);
        } catch (Exception $error) {
            DB::rollback();
            return $this->error($error);
        }
    }

}

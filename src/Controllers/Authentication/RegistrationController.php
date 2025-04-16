<?php

namespace VanDmade\Cuztomisable\Controllers\Authentication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Requests\Authentication\RegistrationRequest;
use VanDmade\Cuztomisable\Mail\Users\Verification as VerificationMail;
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
            return response()->json([$data], 500);
            // Creates the user
            $user = Users\User::create([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'suffix' => $data['suffix'] ?? null,
                'title' => $data['title'] ?? null,
                'username' => $data['username'] ?? null,
                'email' => $data['email'],
                'password' => $password = Hash::make($data['password']),
                'gender' => $data['gender'] ?? null,
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
                // TODO :: Send a notification to the creator of the invitation
                $user->created_by = $registration->created_by;
                $user->save();
            }
            $hasMobile = false;
            $mobilePhone = null;
            // Iterates through the phones to allow for multiple phone numbers to be entered on use creation
            foreach ($data['phones'] ?? [] as $i => $phone) {
                Phone::create([
                    'user_id' => $user->id,
                    'number' => $phone['number'],
                    'country_code' => $phone['country_code'] ?? 1,
                    'extension' => $phone['extension'] ?? null,
                    'mobile' => $hasMobile = isset($phone['mobile']) && $phone['mobile'] == '1' ? true : false,
                    'default' => !isset($phone['default']) || $phone['default'] == '1' ? true : false,
                ]);
            }
            // Iterates through the addresses to add them to the address database
            foreach ($data['addresses'] ?? [] as $i => $address) {
                Address::create([
                    'user_id' => $user->id,
                    'address' => $address['address'],
                    'address_two' => $address['address_two'] ?? null,
                    'address_three' => $address['address_three'] ?? null,
                    'state_or_province' => $address['state_or_province'],
                    'city' => $address['city'],
                    'country' => $address['country'],
                    'zip_or_postal_code' => $address['zip_or_postal_code'],
                    'shipping' => isset($address['shipping']) && $address['shipping'] == '1' ? true : false,
                    'billing' => isset($address['billing']) && $address['billing'] == '1' ? true : false,
                ]);
            }
            // Creates the phone entry for the user
            if (config('cuztomisable.authentication.notifications.email_verification', false) !== false) {
                // TODO :: Sends the email verification message to the user
                $this->email(new VerificationMail($user), $user->email);
            }
            if ($hasMobile && config('cuztomisable.authentication.notifications.phone_verification', false) !== false) {
                // TODO :: Sends the phone verification text to the user
            }
            DB::commit();
            return $this->success([
                'message' => __('cuztomisable/authentication.registration.created'),
            ]);
        } catch (Exception $error) {
            DB::rollback();
            return $this->error($error);
        }
    }

}

<?php

namespace VanDmade\Cuztomisable\Controllers\Authentication;

use VanDmade\Cuztomisable\Controllers\Controller;
use Illuminate\Http\Request;
use VanDmade\Cuztomisable\Requests\TablelifyRequest;
use VanDmade\Cuztomisable\Requests\Authentication\RegistrationRequest;
use VanDmade\Cuztomisable\Requests\Authentication\InviteRequest;
use VanDmade\Cuztomisable\Mail\Users\Verification as VerificationMail;
use VanDmade\Cuztomisable\Mail\Users\Registered as RegisteredMail;
use VanDmade\Cuztomisable\Mail\Users\Invitation as InvitationMail;
use VanDmade\Cuztomisable\Models\Address;
use VanDmade\Cuztomisable\Models\Phone;
use VanDmade\Cuztomisable\Models\Users;
use VanDmade\Cuztomisable\Helpers\Tablelify;
use Auth;
use Carbon\Carbon;
use DB;
use Exception;
use Hash;

class RegistrationController extends Controller
{

    public function verify($code)
    {
        try {
            $registration = Users\Registration::where('code', '=', $code)
                ->orderBy('id', 'desc')
                ->withTrashed()
                ->first();
            if (!isset($registration->id)) {
                throw new Exception(__('cuztomisable/authentication.registration.errors.not_found'), 404);
            }
            if (!is_null($registration->deleted_at)) {
                throw new Exception(__('cuztomisable/authentication.registration.errors.deleted'), 404);
            }
            if (!is_null($registration->used_at)) {
                throw new Exception(__('cuztomisable/authentication.registration.errors.used'), 401);
            }
            if (!is_null($registration->expires_at) && strtotime($registration->expires_at) < time()) {
                throw new Exception(__('cuztomisable/authentication.registration.errors.expired'), 403);
            }
            $phone = explode(' ', trim($registration->phone, '+'), 2);
            $countryCode = $phone[0] ?? '';
            $phone = $phone[1] ?? '';
            return $this->success([
                'message' => __('cuztomisable/authentication.registration.verified'),
                'user' => [
                    'name' => $registration->name,
                    'email' => empty($registration->email) ? '' : $registration->email,
                    'phone' => [
                        'country_code' => $countryCode,
                        'number' => $phone,
                    ],
                ],
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function table(TablelifyRequest $request)
    {
        try {
            $data = $request->validated();
            $query = Users\Registration::select('user_registrations.id', 'user_registrations.name',
                'user_registrations.email', 'user_registrations.phone', 'user_registrations.expires_at',
                'user_registrations.sent_at', 'u.name as creator','u.email as creator_email',
                'user_registrations.used_at', 'user_registrations.expires_at as expired_ago',
                'user_registrations.sent_at as resend_in')
                ->leftJoin('users as u', 'u.id', '=', 'user_registrations.created_by')
                ->where(function ($query) use ($data) {
                    $query->where('user_registrations.name', 'LIKE', $data['search'])
                        ->orWhere('user_registrations.email', 'LIKE', $data['search'])
                        ->orWhere('user_registrations.phone', 'LIKE', $data['search'])
                        ->orWhere('u.name', 'LIKE', $data['search'])
                        ->orWhere('u.email', 'LIKE', $data['search']);
                })
                ->where('expires_at', '>=', date('Y-m-d H:i:s', strtotime('-1 day')))
                ->where(function($query) {
                    $query->orWhereNull('used_at')
                        ->orWhere('used_at', '>=', date('Y-m-d H:i:s', strtotime('-1 day')));
                });
            /*$data['columns'] = [
                'name' => 'user_registrations.name',
                'creator' => 'u.name',
                'expires_at' => 'user_registrations.exdpires_at',
                'used' => DB::raw('IF(user_registrations.used_at IS NULL, true, false)'),
            ];*/
            return Tablelify::run($query, $data);
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
            $user = config('auth.providers.users.model')::create([
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
            if (config('cuztomisable.account.address') !== false &&
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
                // Sends the phone verification text to the user
                $message = __('cuztomisable/authentication.registration.sms.verification', [
                    'company' => env('APP_NAME'),
                    'url' => url('/registration/'.$registration->code),
                ]);
                $this->text($message, $phone->country_code, $phone->number);
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

    public function invite(InviteRequest $request)
    {
        try {
            $data = $request->validated();
            $phone = isset($data['phone'], $data['country_code']) ?
                '+'.$data['country_code'].' '.$data['phone'] : null;
            $email = $data['email'] ?? null;
            if (empty($phone) && empty($email)) {
                throw new Exception(__('cuztomisable/authentication.registration.errors.values_missing'), 422);
            }
            // Checks for a recent sending
            $recent = Users\Registration::whereNull('used_at')
                ->where(function($query) {
                    $query->orWhereNull('expires_at')
                        ->orWhere('expires_at', '>=', date('Y-m-d H:i:s'));
                })
                ->where(function($query) use ($email, $phone) {
                    if (!empty($email)) {
                        $query->where('email', '=', $email);
                    } else {
                        $query->where('phone', '=', $phone);
                    }
                })
                ->orderBy('id', 'desc')
                ->first();
            if (isset($recent->id)) {
                throw new Exception(__('cuztomisable/authentication.registration.errors.recently_invited'), 404);
            }
            // Checks for a user that already exists in the system based on the email or phone
            $exists = config('auth.providers.users.model')::where(function ($query) use ($email, $phone) {
                if (!empty($email)) {
                    $query->where('email', '=', $email);
                } else {
                    $query->whereHas('phones', function ($q) use ($phone) {
                        [$countryCode, $phone] = explode(' ', trim($phone, '+'));
                        $q->where('number', '=', $phone)
                            ->where('country_code', '=', $countryCode);
                    });
                }
            })->exists();
            if ($exists) {
                throw new Exception(__('cuztomisable/authentication.registration.errors.already_used'), 409);
            }
            // Finds any older registrations for that specific phone or email and deletes them
            Users\Registration::where(function($query) use ($phone, $email) {
                // Determines if the user is using a phone or email
                if (!is_null($phone)) {
                    $query->where('phone', '=', $phone);
                } else {
                    $query->where('email', '=', $email);
                }
            })->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => Auth::user()->id,
            ]);
            $registration = new Users\Registration();
            $registration->name = $data['name'];
            $registration->phone = $phone;
            $registration->email = $email;
            $registration->save();
            // Sends the registration notification to the user
            if (!is_null($registration->email)) {
                $this->email(new InvitationMail($registration), $registration->email);
            } else {
                $message = __('cuztomisable/authentication.registration.sms.invited', [
                    'company' => env('APP_NAME'),
                    'url' => url('/registration/'.$registration->code),
                ]);
                $this->text($message, $data['country_code'], $data['phone']);
            }
            return $this->success([
                'message' => __('cuztomisable/authentication.registration.invite'),
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function send($id)
    {
        try {
            $registration = Users\Registration::where('id', '=', $id)
                ->whereNull('used_at')
                ->where(function($query) {
                    $query->orWhereNull('expires_at')
                        ->orWhere('expires_at', '>=', date('Y-m-d H:i:s', strtotime('-1 day')));
                })->first();
            if (!isset($registration->id)) {
                throw new Exception(__('cuztomisable/authentication.registration.errors.missing'), 404);
            }
            $expired = Carbon::parse($registration->expires_at)->lt(Carbon::now());
            $resendAfter = config('cuztomisable.account.registration.resend_after', 300);
            // Makes sure that the registration link was sent recently
            if (!is_null($registration->sent_at) &&
                strtotime($registration->sent_at) >= strtotime('-'.$resendAfter.' seconds')) {
                throw new Exception(__('cuztomisable/authentication.registration.errors.sent_recently'), 429);
            }
            $registration->sent_at = date('Y-m-d H:i:s');
            $seconds = config('cuztomisable.account.registration.expires_in', 300);
            $registration->expires_at = date('Y-m-d H:i:s', strtotime('+'.$seconds.' seconds'));
            $registration->save();
            // Sends the registration notification to the user
            if (!is_null($registration->email)) {
                $this->email(new InvitationMail($registration), $registration->email);
            } else {
                // Send the registration code/link via text
                $message = __('cuztomisable/authentication.registration.sms.invited', [
                    'company' => env('APP_NAME'),
                    'url' => url('/registration/'.$registration->code),
                ]);
                $this->text($message, $registration->phone);
            }
            return $this->success([
                'message' => __('cuztomisable/authentication.registration.resent'),
                'resend_in' => $resendAfter,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete($id)
    {
        try {
            // Finds any older registrations for that specific phone or email and deletes them
            $registration = Users\Registration::where('id', '=', $id)->withTrashed()->first();
            if ($deleted = $registration->trashed()) {
                // Updates the expiration date
                $seconds = config('cuztomisable.account.registration.expires_in', 300);
                $registration->expires_at = date('Y-m-d H:i:s', strtotime('+'.$seconds.' seconds'));
            }
            // Resets OR sets the deleted at parameters for soft deletion
            $registration->deleted_by = $deleted ? null : Auth::user()->id;
            $registration->deleted_at = $deleted ? null : date('Y-m-d H:i:s');
            $registration->save();
            return $this->success([
                'message' => __('cuztomisable/authentication.registration.'.($deleted ? 'undo' : 'deleted')),
                'deleted' => $deleted,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

}

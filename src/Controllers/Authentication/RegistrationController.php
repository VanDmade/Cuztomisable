<?php

namespace VanDmade\Cuztomisable\Controllers\Authentication;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use VanDmade\Cuztomisable\Controllers\Controller;
use VanDmade\Cuztomisable\Helpers\Tablelify;
use VanDmade\Cuztomisable\Mail\Users\Invitation as InvitationMail;
use VanDmade\Cuztomisable\Mail\Users\Registered as RegisteredMail;
use VanDmade\Cuztomisable\Mail\Users\Verification as VerificationMail;
use VanDmade\Cuztomisable\Models\Address;
use VanDmade\Cuztomisable\Models\Phone;
use VanDmade\Cuztomisable\Models\Users;
use VanDmade\Cuztomisable\Requests\Authentication\InviteRequest;
use VanDmade\Cuztomisable\Requests\Authentication\RegistrationRequest;
use VanDmade\Cuztomisable\Requests\TablelifyRequest;

class RegistrationController extends Controller
{

    public function verify(Request $request, $code): JsonResponse
    {
        try {
            $this->rateLimit(
                'cuztomisable:registration:verify:'.implode(':', [$request->ip(), $code]),
                'cuztomisable/authentication.registration.errors.not_found'
            );
            $registration = Users\Registration::where('code', '=', $code)
                ->orderBy('id', 'desc')
                ->withTrashed()
                ->first();
            if (!isset($registration->id)) {
                throw new Exception(__('cuztomisable/authentication.registration.errors.not_found'), 404);
            }
            if (!is_null($registration->deleted_at)) {
                $this->incrementRegistrationAttempt($registration);
                throw new Exception(__('cuztomisable/authentication.registration.errors.deleted'), 404);
            }
            if (!is_null($registration->used_at)) {
                $this->incrementRegistrationAttempt($registration);
                throw new Exception(__('cuztomisable/authentication.registration.errors.used'), 401);
            }
            if (!is_null($registration->expires_at) && $registration->expires_at->isPast()) {
                $this->incrementRegistrationAttempt($registration);
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

    public function table(TablelifyRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $query = Users\Registration::select('user_registrations.id', 'user_registrations.name',
                'user_registrations.email', 'user_registrations.phone', 'user_registrations.expires_at',
                'user_registrations.sent_at', 'u.name as creator','u.email as creator_email',
                'user_registrations.used_at', 'user_registrations.expires_at as expired_ago',
                'user_registrations.sent_at as resend_in')
                ->leftJoin('users as u', 'u.id', '=', 'user_registrations.created_by')
                ->where('expires_at', '>=', now()->subDay())
                ->where(function($query) {
                    $query->orWhereNull('used_at')
                        ->orWhere('used_at', '>=', now()->subDay());
                });
            /*$data['columns'] = [
                'name' => 'user_registrations.name',
                'creator' => 'u.name',
                'expires_at' => 'user_registrations.exdpires_at',
                'used' => DB::raw('IF(user_registrations.used_at IS NULL, true, false)'),
            ];*/
            $parameters = [
                'allowed_columns' => [
                    'user_registrations.id',
                    'user_registrations.name',
                    'user_registrations.email',
                    'user_registrations.phone',
                    'user_registrations.expires_at',
                    'user_registrations.sent_at',
                    'user_registrations.used_at',
                    'u.name',
                    'u.email',
                ],
                'search_columns' => [
                    'user_registrations.name',
                    'user_registrations.email',
                    'user_registrations.phone',
                    'u.name',
                    'u.email',
                ],
                'allowed_filters' => ['user_registrations.created_by', 'user_registrations.used_at'],
                'default_columns' => ['user_registrations.id' => 'desc'],
            ];
            return Tablelify::run($query, array_merge($data, $parameters));
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(RegistrationRequest $request, $code = null): JsonResponse
    {
        try {
            $response = DB::transaction(function () use ($request, $code) {
                $data = $request->validated();
                $identifier = strtolower($data['email'] ?? ($data['phone'] ?? ''));
                $this->rateLimit(
                    'cuztomisable:registration:save:'.implode(':', [$request->ip(), $identifier]),
                    'cuztomisable/authentication.registration.errors.not_found'
                );
                $sendRegisteredTo = null;
                $registration = null;
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
                    // Gets the registration to be updated with the new user's information
                    $registration = Users\Registration::where('code', '=', $code)
                        ->whereNull('used_at')
                        ->where(function($query) {
                            $query->orWhereNull('expires_at')
                                ->orWhere('expires_at', '>=', now());
                        })
                        ->first();
                    if (!isset($registration->id)) {
                        throw new Exception(__('cuztomisable/authentication.registration.errors.not_found'), 404);
                    }
                    $registration->user_id = $user->id;
                    $registration->used_at = now();
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
                if (!empty($data['phone'])) {
                    $phone = Phone::create([
                        'user_id' => $user->id,
                        'number' => $data['phone'],
                        'country_code' => $data['country_code'] ?? 1,
                        'default' => true,
                    ]);
                }
                // Makes sure the address is entered or if it needs to be ignored
                if (config('cuztomisable.account.address') !== false &&
                    !empty($data['address'])) {
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
                if (config('cuztomisable.notifications.email_verification', false) !== false) {
                    // Sends the email verification message to the user
                    $this->email(new VerificationMail($user), $user->email);
                }
                if (isset($phone->id) &&
                    config('cuztomisable.notifications.phone_verification', false) !== false &&
                    isset($registration->code)) {
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
                    $this->email(new RegisteredMail($user), $sendRegisteredTo);
                }
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
            });
            return $response;
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function invite(InviteRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $data = $request->validated();
                $phone = isset($data['phone'], $data['country_code']) ?
                    '+'.$data['country_code'].' '.$data['phone'] : null;
                $email = $data['email'] ?? null;
                $identifier = strtolower($email ?? $phone ?? '');
                $this->rateLimit(
                    'cuztomisable:registration:invite:'.implode(':', [$request->ip(), $identifier]),
                    'cuztomisable/authentication.registration.errors.recently_invited'
                );
                if (empty($phone) && empty($email)) {
                    throw new Exception(__('cuztomisable/authentication.registration.errors.values_missing'), 422);
                }
                // Checks for a recent sending
                $recent = Users\Registration::whereNull('used_at')
                    ->where(function($query) {
                        $query->orWhereNull('expires_at')
                            ->orWhere('expires_at', '>=', now());
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
                    'deleted_at' => now(),
                    'deleted_by' => $this->actorId(),
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
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function send(Request $request, $id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                $this->rateLimit(
                    'cuztomisable:registration:send:'.implode(':', [$request->ip(), (string) $id]),
                    'cuztomisable/authentication.registration.errors.sent_recently'
                );
                $registration = Users\Registration::where('id', '=', $id)
                    ->whereNull('used_at')
                    ->where(function($query) {
                        $query->orWhereNull('expires_at')
                            ->orWhere('expires_at', '>=', now()->subDay());
                    })->first();
                if (!isset($registration->id)) {
                    throw new Exception(__('cuztomisable/authentication.registration.errors.missing'), 404);
                }
                $resendAfter = config('cuztomisable.account.registration.resend_after', 300);
                // Makes sure that the registration link was sent recently
                if (!is_null($registration->sent_at) &&
                    $registration->sent_at->gte(now()->subSeconds($resendAfter))) {
                    throw new Exception(__('cuztomisable/authentication.registration.errors.sent_recently'), 429);
                }
                $registration->sent_at = now();
                $seconds = config('cuztomisable.account.registration.expires_in', 300);
                $registration->expires_at = now()->addSeconds($seconds);
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
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function toggleDelete($id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                // Finds any older registrations for that specific phone or email and deletes them
                $registration = Users\Registration::where('id', '=', $id)->withTrashed()->first();
                if ($deleted = $registration->trashed()) {
                    // Updates the expiration date
                    $seconds = config('cuztomisable.account.registration.expires_in', 300);
                    $registration->expires_at = now()->addSeconds($seconds);
                }
                // Resets OR sets the deleted at parameters for soft deletion
                $registration->deleted_by = $deleted ? null : $this->actorId();
                $registration->deleted_at = $deleted ? null : now();
                $registration->save();
                return $this->success([
                    'message' => __('cuztomisable/authentication.registration.'.($deleted ? 'undo' : 'deleted')),
                    'deleted' => $deleted,
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    private function incrementRegistrationAttempt(Users\Registration $registration): void
    {
        $maxAttempts = (int) config('cuztomisable.account.registration.attempts.max', 5);
        $registration->attempt_counter = (int) $registration->attempt_counter + 1;
        $registration->save();
        if ($registration->attempt_counter >= $maxAttempts) {
            $registration->delete();
        }
    }

}

<?php

namespace VanDmade\Cuztomisable\Services\Authentication;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use VanDmade\Cuztomisable\Events\UserInvited;
use VanDmade\Cuztomisable\Events\UserRegistered;
use VanDmade\Cuztomisable\Jobs\SendText;
use VanDmade\Cuztomisable\Mail\Users\Invitation as InvitationMail;
use VanDmade\Cuztomisable\Mail\Users\Registered as RegisteredMail;
use VanDmade\Cuztomisable\Mail\Users\Verification as VerificationMail;
use VanDmade\Cuztomisable\Models\Users;
use VanDmade\Cuztomisable\Services\AddressService;
use VanDmade\Cuztomisable\Services\PhoneService;
use VanDmade\Cuztomisable\Services\TableService;

class RegistrationService
{

    public function __construct(
        protected readonly PhoneService $phoneService,
        protected readonly AddressService $addressService
    ) {
    }

    public function verify(string $code): array
    {
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
        // Registrations made via email-only invites have no phone at all - splitPhoneNumber()
        // already guards against that, rather than trim()'ing a null (deprecated in PHP 8.1+).
        [$countryCode, $phone] = splitPhoneNumber($registration->phone);
        return [
            'name' => $registration->name,
            'email' => empty($registration->email) ? '' : $registration->email,
            'phone' => [
                'country_code' => $countryCode ?? '',
                'number' => $phone ?? '',
            ],
        ];
    }

    public function table(array $data): JsonResponse
    {
        $query = Users\Registration::select('user_registrations.id', 'user_registrations.name',
            'user_registrations.email', 'user_registrations.phone', 'user_registrations.expires_at',
            'user_registrations.sent_at', 'u.name as creator', 'u.email as creator_email',
            'user_registrations.used_at', 'user_registrations.expires_at as expired_ago',
            'user_registrations.sent_at as resend_in')
            ->leftJoin('users as u', 'u.id', '=', 'user_registrations.created_by')
            ->where('expires_at', '>=', now()->subDay())
            ->where(function($query) {
                $query->orWhereNull('used_at')
                    ->orWhere('used_at', '>=', now()->subDay());
            });
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
        return TableService::run($query, array_merge($data, $parameters));
    }

    public function save(array $data, ?string $code): Model
    {
        return DB::transaction(function() use ($data, $code) {
            $sendRegisteredTo = null;
            $registration = null;
            // Creates the user
            $user = config('auth.providers.users.model')::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => !empty($data['username']) ? $data['username'] : null,
                'password' => $password = Hash::make($data['password']),
                'timezone' => !empty($data['timezone']) ? $data['timezone'] : config('cuztomisable.account.default_timezone', 'America/New_York'),
                'locked' => config('cuztomisable.account.locked_by_default', true),
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
                $phone = $this->phoneService->setDefault($user, $data['phone'], $data['country_code'] ?? null);
            }
            // Makes sure the address is entered or if it needs to be ignored
            if (config('cuztomisable.account.address') !== false && !empty($data['address'])) {
                $this->addressService->setDefault($user, $data);
            }
            // Sends the email verification message to the user
            if (config('cuztomisable.notifications.email_verification.enabled', true)) {
                Mail::to($user->email)->send(new VerificationMail($user));
            }
            if (isset($phone->id) &&
                config('cuztomisable.notifications.phone_verification.enabled', true) &&
                isset($registration->code)) {
                // Sends the phone verification text to the user - kept distinct from any
                // response-facing message, unlike the old controller which reused $message for both.
                $smsMessage = __('cuztomisable/text.registration.verification', [
                    'company' => env('APP_NAME'),
                    'url' => url('/registration/'.$registration->code),
                ]);
                SendText::dispatch($phone->country_code, $phone->number, $smsMessage);
            }
            // Determines if the admin or creator should be notified about the recent registration
            if (!is_null($sendRegisteredTo) && config('cuztomisable.account.registration.send_notification', false)) {
                Mail::to($sendRegisteredTo)->send(new RegisteredMail($user));
            }
            UserRegistered::dispatch($user);
            return $user;
        });
    }

    public function invite(array $data): Users\Registration
    {
        return DB::transaction(function() use ($data) {
            $phone = generatePhoneNumber($data['country_code'] ?? null, $data['phone'] ?? null);
            $email = $data['email'] ?? null;
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
            $exists = config('auth.providers.users.model')::where(function($query) use ($email, $phone) {
                if (!empty($email)) {
                    $query->where('email', '=', $email);
                } else {
                    $query->whereHas('phones', function($q) use ($phone) {
                        [$countryCode, $number] = splitPhoneNumber($phone);
                        $q->where('number', '=', $number)
                            ->where('country_code', '=', $countryCode);
                    });
                }
            })->exists();
            if ($exists) {
                throw new Exception(__('cuztomisable/authentication.registration.errors.already_used'), 409);
            }
            Users\Registration::where(function($query) use ($phone, $email) {
                if (!is_null($phone)) {
                    $query->where('phone', '=', $phone);
                } else {
                    $query->where('email', '=', $email);
                }
            })->get()->each->delete();
            $registration = new Users\Registration();
            $registration->name = $data['name'];
            $registration->phone = $phone;
            $registration->email = $email;
            $registration->save();
            // Sends the registration notification to the user
            if (!is_null($registration->email)) {
                Mail::to($registration->email)->send(new InvitationMail($registration));
            } else {
                $smsMessage = __('cuztomisable/text.registration.invited', [
                    'company' => env('APP_NAME'),
                    'url' => url('/registration/'.$registration->code),
                ]);
                SendText::dispatch($data['country_code'], $data['phone'], $smsMessage);
            }
            UserInvited::dispatch($registration);
            return $registration;
        });
    }

    public function send(Users\Registration $registration): Users\Registration
    {
        return DB::transaction(function() use ($registration) {
            $tooStale = !is_null($registration->expires_at) && $registration->expires_at->lt(now()->subDay());
            if (!is_null($registration->used_at) || $tooStale) {
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
                Mail::to($registration->email)->send(new InvitationMail($registration));
            } else {
                $smsMessage = __('cuztomisable/text.registration.invited', [
                    'company' => env('APP_NAME'),
                    'url' => url('/registration/'.$registration->code),
                ]);
                [$countryCode, $number] = splitPhoneNumber($registration->phone);
                SendText::dispatch($countryCode ?? '', $number ?? '', $smsMessage);
            }
            return $registration;
        });
    }

    public function toggleDelete(Users\Registration $registration): bool
    {
        return DB::transaction(function() use ($registration) {
            $deleted = $registration->trashed();
            if ($deleted) {
                // If the registration link was deleted we need to reset the expiration time to give the user a chanace
                $seconds = config('cuztomisable.account.registration.expires_in', 300);
                $registration->expires_at = now()->addSeconds($seconds);
                $registration->undo();
            } else {
                // Bye bye registration
                $registration->delete();
            }
            return $deleted;
        });
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

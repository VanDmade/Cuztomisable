<?php

namespace VanDmade\Cuztomisable\Services\Users;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use VanDmade\Cuztomisable\Events\NewUser;
use VanDmade\Cuztomisable\Jobs\SendText;
use VanDmade\Cuztomisable\Mail\Users\Passwords\Temporary as TemporaryMail;
use VanDmade\Cuztomisable\Mail\Users\Verification as VerificationMail;
use VanDmade\Cuztomisable\Services\AddressService;
use VanDmade\Cuztomisable\Services\ImageService;
use VanDmade\Cuztomisable\Services\PhoneService;
use VanDmade\Cuztomisable\Services\RefreshTokenService;
use VanDmade\Cuztomisable\Services\TableService;

/**
 * CRUD and account-management actions (lock, reset attempts, etc) for users.
 */
class UserService
{

    public function __construct(
        protected readonly ImageService $imageService,
        protected readonly PhoneService $phoneService,
        protected readonly AddressService $addressService,
        protected readonly RefreshTokenService $refreshTokenService
    ) {
    }

    public function find(Model $actor, ?int $id): Model
    {
        return $this->resolveTarget($actor, $id);
    }

    public function updateTimezone(Model $user, string $timezone): bool
    {
        // A manual override (set via the Details form) sticks until the user resets it back to
        // Automatic - the periodic browser-detected sync must not silently clobber that choice.
        if (!($user->timezone_auto ?? true) || $user->timezone === $timezone) {
            return false;
        }
        $user->timezone = $timezone;
        $user->save();
        return true;
    }

    public function table(array $data): JsonResponse
    {
        $query = config('auth.providers.users.model')::select('users.id', 'users.name', 'users.email',
            'users.username', 'ip.last_used_at', 'p.number as phone', 'p.country_code',
            'p.verified_at as phone_verified_at', 'users.email_verified_at',
            'users.admin', 'users.locked', 'users.multi_factor_authentication as mfa',
            DB::raw("CASE WHEN img.path IS NOT NULL THEN CONCAT('".rtrim(config('app.url'), '/')."/storage/', img.path) ELSE NULL END as image"))
            ->leftJoin('phones as p', function ($join) {
                $join->on('p.user_id', '=', 'users.id')
                    ->where('p.default', '=', true);
            })
            ->leftJoin('user_ip_addresses as ip', function ($join) {
                $join->on('ip.user_id', '=', 'users.id')
                    // Grabs the latest login attempt for this user
                    ->whereRaw('ip.id=(SELECT temp.id FROM user_ip_addresses as temp WHERE temp.user_id=ip.user_id ORDER BY temp.last_used_at DESC LIMIT 1)');
            })
            // Only joins the currently-active (non-deleted, still-on-disk) image -
            // the table lists a URL directly since it bypasses UserResource/profile().
            ->leftJoin('images as img', function ($join) {
                $join->on('img.id', '=', 'users.image_id')
                    ->whereNull('img.deleted_at')
                    ->whereNull('img.removed_from_storage_at');
            })
            ->where(function ($query) {
                $query->whereNotNull('users.id');
            });
        $parameters = [
            'allowed_columns' => [
                'users.id',
                'users.name',
                'users.email',
                'users.username',
                'ip.last_used_at',
                'users.email_verified_at',
                'users.admin',
                'users.locked',
            ],
            'search_columns' => ['users.name', 'users.email', 'users.username', 'p.number'],
            'allowed_filters' => ['users.admin', 'users.locked'],
            'default_columns' => ['users.id' => 'desc'],
        ];
        return TableService::generate($query, array_merge($data, $parameters));
    }

    public function save(
        Model $actor,
        ?int $id,
        array $data,
        ?UploadedFile $image,
        bool $clearImage
    ): Model {
        return DB::transaction(function() use ($actor, $id, $data, $image, $clearImage) {
            $user = $this->resolveTarget($actor, $id);
            $emailChanged = $data['email'] !== $user->email;
            $user->name = $data['name'] ?? null;
            $user->username = $data['username'] ?? null;
            $user->email = $data['email'];
            if ($emailChanged) {
                // A changed email inherits nothing from the old address's verified status
                $user->email_verified_at = null;
            }
            // Choosing a timezone manually here sticks until reset back to Automatic - otherwise
            // the periodic browser-detected sync (see updateTimezone()) would just overwrite it.
            $timezoneAuto = filter_var($data['timezone_auto'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $user->timezone_auto = $timezoneAuto;
            if (!$timezoneAuto && !empty($data['timezone'])) {
                $user->timezone = $data['timezone'];
            } elseif ($user->timezone === null) {
                $user->timezone = config('cuztomisable.account.default_timezone', 'America/New_York');
            }
            if (config('cuztomisable.login.multi_factor_authentication.allowed', true) && !empty($data['mfa'])) {
                $user->multi_factor_authentication = $data['mfa'] == '1';
            }
            $user->save();
            if ($emailChanged && config('cuztomisable.notifications.email_verification.enabled', true)) {
                Mail::to($user->email)->send(new VerificationMail($user));
            }
            $existingPhone = $user->defaultPhone;
            $oldNumber = $existingPhone?->number;
            $oldCountryCode = $existingPhone?->country_code;
            $this->applyContactAndImage($user, $data, $image, $clearImage);
            // Refreshes the user model to make sure everything is updated
            $user->refresh();
            $phone = $user->defaultPhone;
            $phoneChanged = $phone && ($phone->number !== $oldNumber || $phone->country_code != $oldCountryCode);
            if ($phoneChanged && config('cuztomisable.notifications.phone_verification.enabled', true)) {
                $this->resendPhoneVerification($actor, $user->id);
            }
            return $user;
        });
    }

    public function create(array $data, ?UploadedFile $image): Model
    {
        return DB::transaction(function() use ($data, $image) {
            $user = new (config('auth.providers.users.model'))();
            $user->name = $data['name'] ?? null;
            $user->username = $data['username'] ?? null;
            $user->email = $data['email'];
            $user->timezone = $data['timezone'] ?? config('cuztomisable.account.default_timezone', 'America/New_York');
            $user->password = Hash::make($password = generateCode(8));
            $user->change_password = true;
            $user->change_password_sent_at = now();
            $user->save();
            Mail::to($user->email)->send(new TemporaryMail($user, $password));
            $this->applyContactAndImage($user, $data, $image, false);
            $user->refresh();
            // Package-owned hook for host apps
            NewUser::dispatch($user);
            return $user;
        });
    }

    private function applyContactAndImage(Model $user, array $data, ?UploadedFile $image, bool $clearImage): void
    {
        // Determines if the phone is set up and entered
        if (!empty($data['phone'])) {
            $this->phoneService->setDefault($user, $data['phone'], $data['country_code'] ?? null);
        }
        // Makes sure the address is entered or if it needs to be ignored
        if (config('cuztomisable.account.address') !== false && !empty($data['address'])) {
            $this->addressService->setDefault($user, $data);
        }
        // Checks to see if the image exists and is valid prior to uploading it to the bucket
        if ($image && $image->isValid()) {
            $uploaded = $this->imageService->upload($image);
            $user->image_id = $uploaded->id;
            $user->save();
        } elseif ($clearImage) {
            if (!is_null($user->image_id)) {
                $this->imageService->delete($user->image_id, true);
            }
            $user->image_id = null;
            $user->save();
        }
    }

    public function toggleLocked(Model $actor, ?int $id): bool
    {
        return DB::transaction(function() use ($actor, $id) {
            $user = $this->resolveTarget($actor, $id);
            if ($user->id == $actor->id) {
                throw new Exception(__('cuztomisable/user.errors.lock_my_account'), 404);
            }
            $locked = $user->locked;
            $user->locked = !$locked;
            $user->save();
            return $locked;
        });
    }

    public function resetAttempts(Model $actor, ?int $id): void
    {
        DB::transaction(function() use ($actor, $id) {
            $user = $this->resolveTarget($actor, $id);
            $user->attempts = 0;
            $user->attempt_timer = null;
            $user->locked = false;
            $user->save();
        });
    }

    public function resendEmailVerification(Model $actor, ?int $id): void
    {
        $user = $this->resolveTarget($actor, $id);
        if (!is_null($user->email_verified_at)) {
            throw new Exception(__('cuztomisable/user.errors.already_verified', ['type' => 'email']), 404);
        }
        Mail::to($user->email)->send(new VerificationMail($user));
    }

    public function resendPhoneVerification(Model $actor, ?int $id): void
    {
        $user = $this->resolveTarget($actor, $id);
        $phone = $user->defaultPhone;
        if (!isset($phone->id)) {
            throw new Exception(__('cuztomisable/user.errors.no_phone_on_file'), 404);
        }
        if (!is_null($phone->verified_at)) {
            throw new Exception(__('cuztomisable/user.errors.already_verified', ['type' => 'phone']), 404);
        }
        $verificationUrl = url('/verification/'.$user->token.'/phone?phone='.$phone->country_code.$phone->number);
        $message = __('cuztomisable/text.registration.verification', [
            'company' => env('APP_NAME'),
            'url' => $verificationUrl,
        ]);
        SendText::dispatch($phone->country_code, $phone->number, $message);
    }

    public function toggleDelete(Model $actor, ?int $id): bool
    {
        return DB::transaction(function() use ($actor, $id) {
            $user = $this->resolveTarget($actor, $id, withTrashed: true);
            if ($user->id == $actor->id) {
                throw new Exception(__('cuztomisable/user.errors.delete_my_account'), 404);
            }
            $deleted = $user->trashed();
            if ($deleted) {
                $user->restore();
            } else {
                $user->delete();
            }
            return $deleted;
        });
    }

    public function toggleMfa(Model $actor, ?int $id): bool
    {
        return DB::transaction(function() use ($actor, $id) {
            $user = $this->resolveTarget($actor, $id);
            $user->multi_factor_authentication = !$user->multi_factor_authentication;
            $user->save();
            return (bool) $user->multi_factor_authentication;
        });
    }

    public function list(): Collection
    {
        return config('auth.providers.users.model')::all()->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'subtitle' => $user->email,
        ]);
    }

    public function refresh(Model $actor): array
    {
        return [
            'cookie' => $actor->generateAuthCookie(),
            'token_expires_at' => optional($actor->tokens()->latest('id')->first()?->expires_at)->toIso8601String(),
        ];
    }

    public function refreshToken(string $plainToken): array
    {
        return DB::transaction(function() use ($plainToken) {
            $token = $this->refreshTokenService->findValid($plainToken);
            if (!$token) {
                throw new Exception(__('cuztomisable/user.refresh.errors.not_found'), 401);
            }
            // Makes sure the user exists and is still active in the system
            if (is_null($token->user)) {
                throw new Exception(__('cuztomisable/user.refresh.errors.user_not_found'), 404);
            }
            // Checks to see if the token was revoked
            if ($token->revoked) {
                throw new Exception(__('cuztomisable/user.refresh.errors.revoked'), 403);
            }
            $newToken = $this->refreshTokenService->renew($token);
            // Issue a new access token
            return [
                'access_token' => $token->user->createToken('mobile')->plainTextToken,
                'refresh_token' => $newToken,
            ];
        });
    }

    public function verification(string $token, string $type, ?string $email, ?string $phone): bool
    {
        $user = config('auth.providers.users.model')::where('token', $token)->first();
        if (!isset($user->id)) {
            return false;
        }
        if ($type === 'email') {
            if ($email && strcasecmp(trim($user->email), trim($email)) === 0) {
                if (is_null($user->email_verified_at)) {
                    $user->email_verified_at = now();
                    $user->save();
                }
                return true;
            }
        } elseif ($type === 'phone') {
            $phone = str_replace(' ', '', $phone ?? '');
            foreach ($user->phones as $phoneRecord) {
                $full = $phoneRecord->country_code.$phoneRecord->number;
                if ($phone === $full) {
                    if (is_null($phoneRecord->verified_at)) {
                        $phoneRecord->verified_at = now();
                        $phoneRecord->save();
                    }
                    return true;
                }
            }
        }
        return false;
    }

    public function enableEmails(Model $actor, ?int $id): void
    {
        $user = $this->resolveTarget($actor, $id);
        $user->disable_emails = false;
        $user->save();
    }

    public function enablePhoneMessages(Model $actor, ?int $id): void
    {
        $user = $this->resolveTarget($actor, $id);
        $phone = $user->defaultPhone;
        if (!isset($phone->id)) {
            throw new Exception(__('cuztomisable/user.errors.no_phone_on_file'), 404);
        }
        $phone->disable_messages = false;
        $phone->save();
    }

    public function unsubscribe(string $token, string $type, ?string $email, ?string $phone): bool
    {
        $user = config('auth.providers.users.model')::where('token', $token)->first();
        if (!isset($user->id)) {
            return false;
        }
        if ($type === 'email') {
            if ($email && strcasecmp(trim($user->email), trim($email)) === 0) {
                $user->disable_emails = true;
                $user->save();
                return true;
            }
        } elseif ($type === 'phone') {
            $phone = str_replace(' ', '', $phone ?? '');
            foreach ($user->phones as $phoneRecord) {
                $full = $phoneRecord->country_code.$phoneRecord->number;
                if ($phone === $full) {
                    $phoneRecord->disable_messages = true;
                    $phoneRecord->save();
                    return true;
                }
            }
        }
        return false;
    }

    // Every caller of this is already reached through a route gated by the specific permission
    // that action requires (manage-users, toggle-user-mfa, etc) - re-checking $actor->admin here
    // on top of that used to silently fall back to acting on the actor's own account for any
    // non-admin user granted one of those permissions directly, instead of the intended target.
    private function resolveTarget(Model $actor, ?int $id, bool $withTrashed = false): Model
    {
        if (is_null($id)) {
            $user = $actor;
        } else {
            $query = config('auth.providers.users.model')::where('id', '=', $id);
            if ($withTrashed) {
                $query->withTrashed();
            }
            $user = $query->first();
        }
        if (!isset($user->id)) {
            throw new Exception(__('cuztomisable/user.errors.not_found'), 404);
        }
        return $user;
    }

}

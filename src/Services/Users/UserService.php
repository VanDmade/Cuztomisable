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
use VanDmade\Cuztomisable\Mail\Users\Passwords\Temporary as TemporaryMail;
use VanDmade\Cuztomisable\Mail\Users\Verification as VerificationMail;
use VanDmade\Cuztomisable\Services\AddressService;
use VanDmade\Cuztomisable\Services\ImageService;
use VanDmade\Cuztomisable\Services\PhoneService;
use VanDmade\Cuztomisable\Services\RefreshTokenService;
use VanDmade\Cuztomisable\Services\TableService;

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
        if ($user->timezone === $timezone) {
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
            'users.admin', 'users.locked', 'users.multi_factor_authentication as mfa')
            ->leftJoin('phones as p', function ($join) {
                $join->on('p.user_id', '=', 'users.id')
                    ->where('p.default', '=', true);
            })
            ->leftJoin('user_ip_addresses as ip', function ($join) {
                $join->on('ip.user_id', '=', 'users.id')
                    // Grabs the latest login attempt for this user
                    ->whereRaw('ip.id=(SELECT temp.id FROM user_ip_addresses as temp WHERE temp.user_id=ip.user_id ORDER BY temp.last_used_at DESC LIMIT 1)');
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
            $user->timezone = $data['timezone'] ?? config('cuztomisable.account.default_timezone', 'America/New_York');
            if (config('cuztomisable.login.multi_factor_authentication.allowed', true) && !empty($data['mfa'])) {
                $user->multi_factor_authentication = $data['mfa'] == '1';
            }
            $user->save();
            if ($emailChanged && config('cuztomisable.notifications.email_verification.enabled', true)) {
                Mail::to($user->email)->send(new VerificationMail($user));
            }
            $this->applyContactAndImage($user, $data, $image, $clearImage);
            // Refreshes the user model to make sure everything is updated
            $user->refresh();
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
            $locked = $user->locked;
            $user->locked = !$locked;
            $user->save();
            return $locked;
        });
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

    private function resolveTarget(Model $actor, ?int $id, bool $withTrashed = false): Model
    {
        if (is_null($id) || !$actor->admin) {
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

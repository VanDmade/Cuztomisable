<?php

namespace VanDmade\Cuztomisable\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Cookie;
use VanDmade\Cuztomisable\Models\Address;
use VanDmade\Cuztomisable\Models\Image;
use VanDmade\Cuztomisable\Models\Permission as PermissionModel;
use VanDmade\Cuztomisable\Models\Phone;
use VanDmade\Cuztomisable\Models\Roles;
use VanDmade\Cuztomisable\Models\Users;
use Exception;

trait User
{

    public static function boot(): void
    {
        parent::boot();
        self::creating(function($model) {
            $model->created_by = Auth::check() ? Auth::user()->id : null;
            $length = (int) config('cuztomisable.account.token_length', 16);
            $length = max(8, min(64, $length));
            $model->token = generateCode($length);
        });
    }

    public function generateAuthCookie(): Cookie
    {
        // Determines the length of time the token will remain active
        $sessionLength = config('cuztomisable.login.session_length');
        $expiresAt = is_null($sessionLength)
            ? now()->addDays(60)
            : now()->addSeconds($sessionLength);
        $token = $this->createToken('access-token');
        $token->accessToken->expires_at = $expiresAt;
        $token->accessToken->save();
        $token = $token->plainTextToken;
        return cookie(
            config('cuztomisable.login.cookie_name', 'api_token'),
            $token,
            now()->diffInMinutes($expiresAt),
            '/',       // path
            null,      // domain
            true,      // secure
            true,      // httpOnly
            false,     // raw
            'Strict'   // sameSite
        );
    }

    public function obscuredEmail(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (is_null($this->email)) {
                    return null;
                }
                list($email, $domain) = explode('@', $this->email);
                return substr_replace($email, '******', 1, strlen($email) - 3).'@'.$domain;
            }
        );
    }

    public function addAttempt(): void
    {
        $this->attempts++;
        if ($this->attempts >= config('cuztomisable.login.attempts.total', 5)) {
            $this->attempts = 0;
            if (config('cuztomisable.login.attempts.locked', false)) {
                $this->locked = true;
            } else {
                $this->attempt_timer = now()->addSeconds(
                    config('cuztomisable.login.attempts.timer', 300)
                );
            }
        }
        if (!is_null($this->attempt_timer) && now()->lessThan($this->attempt_timer)) {
            $this->attempts = 0;
            $this->save();
            throw new Exception(__('cuztomisable/authentication.login.errors.attempts'), 401);
        }
        $this->save();
    }

    public function canLogIn(): bool
    {
        if ($this->locked) {
            throw new Exception(__('cuztomisable/authentication.login.errors.locked'), 401);
        }
        $this->canChangePassword();
        // Checks to see if the user has verified their email and whether it's required
        if (config('cuztomisable.login.verification.email', false) &&
            is_null($this->email_verified_at)) {
            throw new Exception(__('cuztomisable/authentication.login.errors.verification.email_required'), 401);
        }
        // Checks to see if the user has verified their phone number and whether it's required
        if (config('cuztomisable.login.verification.phone', false) &&
            !$this->phones()->whereNotNull('verified_at')->exists()) {
            throw new Exception(__('cuztomisable/authentication.login.errors.verification.phone_required'), 401);
        }
        return true;
    }

    public function canChangePassword(): void
    {
        // Checks to see if the attempt timer is waiting for expiration
        if (!is_null($this->attempt_timer) && now()->lessThan($this->attempt_timer)) {
            throw new Exception(__('cuztomisable/authentication.login.errors.attempts'), 401);
        }
    }

    public function canUsePassword(string $new): void
    {
        // Prevents the user from user previously used passwords
        if (!is_null(config('cuztomisable.account.passwords.reuse_after', 3))) {
            $passwords = $this->passwords()
                ->orderBy('id', 'desc')
                ->limit(config('cuztomisable.account.passwords.reuse_after', 3))
                ->get();
            foreach ($passwords as $password) {
                if (Hash::check($new, $password->password)) {
                    throw new Exception(__('cuztomisable/authentication.passwords.errors.used_recently'), 404);
                }
            }
        }
    }

    public function roles(): HasManyThrough
    {
        return $this->hasManyThrough(
            Roles\Role::class,
            Users\Role::class,
            'user_id',
            'id',
            'id',
            'role_id'
        );
    }

    public function roleLinks(): HasMany
    {
        return $this->hasMany(Users\Role::class, 'user_id');
    }

    public function permissions(): HasManyThrough
    {
        return $this->hasManyThrough(
            PermissionModel::class,
            Users\Permission::class,
            'user_id',
            'id',
            'id',
            'permission_id'
        );
    }

    public function permissionLinks(): HasMany
    {
        return $this->hasMany(Users\Permission::class, 'user_id');
    }

    public function permissionSlugs(): Collection
    {
        return $this->permissions->pluck('slug');
    }

    public function getAllPermissions(): Collection
    {
        // Permissions from roles
        $rolePermissions = $this->roles()->with('permissions')->get()
            ->pluck('permissions')
            ->flatten();
        return $this->permissions->merge($rolePermissions)->unique('id');
    }

    public function hasPermission(string $slug): bool
    {
        return $this->getAllPermissions()->contains('slug', $slug);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Image::class, 'id', 'image_id');
    }

    public function phones(): HasMany
    {
        return $this->hasMany(Phone::class, 'user_id');
    }

    public function mobilePhone(): HasOne
    {
        return $this->hasOne(Phone::class, 'user_id')
            ->where('mobile', '=', true)
            ->where('default', '=', true);
    }

    public function defaultPhone(): HasOne
    {
        return $this->hasOne(Phone::class, 'user_id')
            ->where('default', '=', true);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'user_id');
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class, 'user_id')
            ->where('default', '=', true);
    }

    public function ipAddresses(): HasMany
    {
        return $this->hasMany(Users\IpAddress::class, 'user_id');
    }

    public function lastIpAddress(): HasOne
    {
        return $this->hasOne(Users\IpAddress::class, 'user_id')
            ->orderBy('last_used_at', 'desc');
    }

    public function registration(): HasOne
    {
        return $this->hasOne(Users\Registration::class, 'user_id');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(Users\Code::class, 'user_id')->orderBy('id', 'desc');
    }

    public function passwords(): HasMany
    {
        return $this->hasMany(Users\Passwords\Password::class, 'user_id');
    }

    public function passwordResets(): HasMany
    {
        return $this->hasMany(Users\Passwords\Reset::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'deleted_by');
    }

    public static function findUserByType(string $username, string $type): ?Model
    {
        // Finds the user based on the email, username, or phone
        /** @var class-string<Authenticatable> $userModel */
        $userModel = config('auth.providers.users.model');
        return $userModel::where(function($query) use ($type, $username) {
            $query->orWhere($type, '=', $username);
            // If the username is null than the system will default to email address
            if ($type == 'username') {
                $query->orWhere(function($query) use ($username) {
                    $query->whereNull('username')
                        ->where('email', '=', $username);
                });
            }
        })
        ->first();
    }

}
<?php

namespace VanDmade\Cuztomisable\Concerns;

use Exception;
use Hash;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Cookie;
use VanDmade\Cuztomisable\Models\Address;
use VanDmade\Cuztomisable\Models\Image;
use VanDmade\Cuztomisable\Models\Permission as PermissionModel;
use VanDmade\Cuztomisable\Models\Phone;
use VanDmade\Cuztomisable\Models\Roles;
use VanDmade\Cuztomisable\Models\Users\Code;
use VanDmade\Cuztomisable\Models\Users\IpAddress;
use VanDmade\Cuztomisable\Models\Users\Passwords;
use VanDmade\Cuztomisable\Models\Users\Permission;
use VanDmade\Cuztomisable\Models\Users\Registration;
use VanDmade\Cuztomisable\Models\Users\Role;

/**
 * Login/lockout rules, permission/role resolution, and the standard set of relationships a
 * Cuztomisable-managed user needs - meant to be composed into a host app's own User model
 * (which owns its table/fillable/casts), not just the concrete Models\Users\User Cuztomisable
 * ships by default. Supersedes the narrower HasCuztomisableAuthentication scaffold - this is
 * the actual home for it, not just the login/lockout slice.
 *
 * createdBy()/deletedBy() aren't here - those come from Auditable/Concerns\SoftDeletes instead,
 * composed alongside this trait rather than duplicated in it.
 */
trait CuztomisableUser
{

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
            '/',     // path
            null,    // domain
            true,    // secure
            true,    // httpOnly
            false,   // raw
            'Strict' // sameSite
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
                $this->attempt_timer = date(
                    'Y-m-d H:i:s',
                    strtotime('+'.config('cuztomisable.login.attempts.timer', 300).' seconds')
                );
            }
        }
        if (!is_null($this->attempt_timer) && strtotime($this->attempt_timer) > time()) {
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
            $this->phones()->whereNotNull('verified_at')->count() == 0) {
            throw new Exception(__('cuztomisable/authentication.login.errors.verification.phone_required'), 401);
        }
        return true;
    }

    public function canChangePassword(): void
    {
        // Checks to see if the attempt timer is waiting for expiration
        if (!is_null($this->attempt_timer) && strtotime($this->attempt_timer) > time()) {
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
            foreach ($passwords as $i => $password) {
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
            Role::class,
            'user_id',
            'id',
            'id',
            'role_id'
        );
    }

    public function roleLinks(): HasMany
    {
        return $this->hasMany(Role::class, 'user_id');
    }

    public function permissions(): HasManyThrough
    {
        return $this->hasManyThrough(
            PermissionModel::class,
            Permission::class,
            'user_id',
            'id',
            'id',
            'permission_id'
        );
    }

    public function permissionLinks(): HasMany
    {
        return $this->hasMany(Permission::class, 'user_id');
    }

    public function permissionSlugs(): Collection
    {
        return $this->getAllPermissions()->pluck('slug');
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
        return $this->hasMany(IpAddress::class, 'user_id');
    }

    public function lastIpAddress(): HasOne
    {
        return $this->hasOne(IpAddress::class, 'user_id')
            ->orderBy('last_used_at', 'desc');
    }

    public function registration(): HasOne
    {
        return $this->hasOne(Registration::class, 'user_id');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(Code::class, 'user_id')->orderBy('id', 'desc');
    }

    public function passwords(): HasMany
    {
        return $this->hasMany(Passwords\Password::class, 'user_id');
    }

    public function passwordResets(): HasMany
    {
        return $this->hasMany(Passwords\Reset::class, 'user_id');
    }

    public static function findUserByType($username, $type): ?self
    {
        // Finds the user based on the email, username, or phone
        return config('auth.providers.users.model')::where(function($query) use ($type, $username) {
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

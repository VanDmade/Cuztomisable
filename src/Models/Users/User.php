<?php

namespace VanDmade\Cuztomisable\Models\Users;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use VanDmade\Cuztomisable\Models\Address;
use VanDmade\Cuztomisable\Models\Image;
use VanDmade\Cuztomisable\Models\Permission as PermissionModel;
use VanDmade\Cuztomisable\Models\Phone;
use VanDmade\Cuztomisable\Models\Roles;
use Auth;
use Exception;
use Hash;

class UserOLD extends Authenticatable
{

    use HasFactory, HasApiTokens, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'username',
        'email',
        'email_verified_at',
        'disable_emails',
        'password',
        'timezone',
        'token',
        'locked',
        'change_password',
        'change_password_sent_at',
        'multi_factor_authentication',
        'admin',
        'attempts',
        'attempt_timer',
        'created_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'disable_emails' => 'boolean',
        'locked' => 'boolean',
        'change_password' => 'boolean',
        'change_password_sent_at' => 'datetime',
        'multi_factor_authentication' => 'boolean',
        'admin' => 'boolean',
        'attempt_timer' => 'datetime',
    ];

    protected $hidden = [
        'attempts',
        'attempt_timer',
        'created_by',
        'deleted_by',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function($model) {
            $model->created_by = Auth::check() ? Auth::user()->id : null;
            $model->token = generateCode(8);
        });
    }

    public function generateAuthCookie()
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
            'api_token',
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

    public function canLogIn(): Bool
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

    public function canUsePassword($new): void
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

    public function roles()
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

    public function roleLinks()
    {
        return $this->hasMany(Role::class, 'user_id');
    }

    public function permissions()
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

    public function permissionLinks()
    {
        return $this->hasMany(Permission::class, 'user_id');
    }

    public function permissionSlugs()
    {
        return $this->permissions->pluck('slug');
    }

    public function getAllPermissions()
    {
        // Permissions from roles
        $rolePermissions = $this->roles()->with('permissions')->get()
            ->pluck('permissions')
            ->flatten();
        return $this->permissions->merge($rolePermissions)->unique('id');
    }

    public function hasPermission($slug)
    {
        return $this->getAllPermissions()->contains('slug', $slug);
    }

    public function profile()
    {
        return $this->hasOne(Image::class, 'id', 'image_id');
    }

    public function phones()
    {
        return $this->hasMany(Phone::class, 'user_id');
    }

    public function mobilePhone()
    {
        return $this->hasOne(Phone::class, 'user_id')
            ->where('mobile', '=', true)
            ->where('default', '=', true);
    }

    public function defaultPhone()
    {
        return $this->hasOne(Phone::class, 'user_id')
            ->where('default', '=', true);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'user_id');
    }

    public function defaultAddress()
    {
        return $this->hasOne(Address::class, 'user_id')
            ->where('default', '=', true);
    }

    public function ipAddresses()
    {
        return $this->hasMany(IpAddress::class, 'user_id');
    }

    public function lastIpAddress()
    {
        return $this->hasOne(IpAddress::class, 'user_id')
            ->orderBy('last_used_at', 'desc');
    }

    public function registration()
    {
        return $this->hasOne(Registration::class, 'user_id');
    }

    public function codes()
    {
        return $this->hasMany(Code::class, 'user_id')->orderBy('id', 'desc');
    }

    public function passwords()
    {
        return $this->hasMany(Passwords\Password::class, 'user_id');
    }

    public function passwordResets()
    {
        return $this->hasMany(Passwords\Reset::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'deleted_by');
    }

    public static function findUserByType($username, $type)
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

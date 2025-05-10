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
use VanDmade\Cuztomisable\Models\Phone;
use Auth;
use Exception;

class User extends Authenticatable
{

    use HasFactory, HasApiTokens, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'username',
        'email',
        'email_verified_at',
        'password',
        'timezone',
        'locked',
        'change_password',
        'multi_factor_authentication',
        'admin',
        'attempts',
        'attempt_timer',
        'created_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'locked' => 'boolean',
        'change_password' => 'boolean',
        'multi_factor_authentication' => 'boolean',
        'admin' => 'boolean',
        'attempt_timer' => 'datetime',
    ];

    protected $hidden = [
        'phone_id',
        'admin',
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
        });
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

    public function canLogIn(): Bool
    {
        if ($this->locked) {
            throw new Exception(__('cuztomisable/authentication.error.locked'), 401);
        }
        // Checks to see if the attempt timer is waiting for expiration
        if (!is_null($this->attempt_timer) && strtotime($this->attempt_timer) > time()) {
            throw new Exception(__('cuztomisable/authentication.login.errors.attempts'), 401);
        }
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

    public function permissions()
    {
        
    }

    public function profile()
    {
        return $this->hasOne(Image::class, 'id', 'image_id');
    }

    public function mobilePhone()
    {
        return $this->hasOne(Phone::class, 'user_id')
            ->where('mobile', '=', true)
            ->where('default', '=', true);
    }

    public function phones()
    {
        return $this->hasMany(Phone::class, 'user_id');
    }

    public function addresses()
    {
        return $this->hasMany(Phone::class, 'user_id');
    }

    public function ipAddresses()
    {
        return $this->hasMany(IpAddress::class, 'user_id');
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
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public static function findUserByType($username, $type)
    {
        // Finds the user based on the email, username, or phone
        return User::where(function($query) use ($type, $username) {
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

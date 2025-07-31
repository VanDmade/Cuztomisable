<?php

namespace VanDmade\Cuztomisable\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VanDmade\Cuztomisable\Observers\Users\IpAddressObserver;
use Auth;

#[ObservedBy([IpAddressObserver::class])]
class IpAddress extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'user_ip_addresses';

    protected $fillable = [
        'ip_address',
        'last_used_at',
        'remember',
        'remember_until',
        'user_id',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'remember' => 'boolean',
        'remember_until' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'user_id',
        'deleted_by',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function($model) {
            $model->ip_address = getIpAddress();
        });
        self::saving(function($model) {
            $model->last_used_at = date('Y-m-d H:i:s');
        });
    }

    public function requireMfa(): Bool
    {
        return config('cuztomisable.login.multi_factor_authentication.allowed', false) &&
            $this->user->multi_factor_authentication && (!$this->remember ||
            ($this->remember && time() > strtotime($this->remember_until))) ? true : false;
    }

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function codes()
    {
        return $this->hasMany(Code::class, 'user_ip_address_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'deleted_by');
    }

}

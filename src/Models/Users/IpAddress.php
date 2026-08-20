<?php

namespace VanDmade\Cuztomisable\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VanDmade\Cuztomisable\Concerns\SoftDeletes;
use VanDmade\Cuztomisable\Observers\Users\IpAddressObserver;

#[ObservedBy([IpAddressObserver::class])]
class IpAddress extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'user_ip_addresses';

    protected $fillable = [
        'ip_address',
        'fingerprint',
        'label',
        'geo_label',
        'latitude',
        'longitude',
        'last_used_at',
        'remember',
        'remember_until',
        'user_id',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'remember' => 'boolean',
        'remember_until' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'user_id',
        'fingerprint',
        'deleted_by',
    ];

    protected static function boot(): void
    {
        parent::boot();
        self::creating(function($model) {
            $model->ip_address = getIpAddress();
        });
    }

    public function requireMfa(): bool
    {
        return config('cuztomisable.login.multi_factor_authentication.allowed', false) &&
            $this->user->multi_factor_authentication && (!$this->remember ||
            ($this->remember && now()->gt($this->remember_until)));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(Code::class, 'user_ip_address_id');
    }

}

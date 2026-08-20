<?php

namespace VanDmade\Cuztomisable\Models\Users;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use VanDmade\Cuztomisable\Concerns\Auditable;
use VanDmade\Cuztomisable\Concerns\HasOrganization;
use VanDmade\Cuztomisable\Concerns\SoftDeletes;

class Registration extends Model
{

    use HasFactory, SoftDeletes, Auditable, HasOrganization;

    protected $table = 'user_registrations';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'code',
        'used_at',
        'sent_at',
        'expires_at',
        'attempt_counter',
        'user_id',
        'organization_id',
        'created_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'deleted_at' => 'datetime',
        'attempt_counter' => 'integer',
    ];

    protected $hidden = [
        'code',
        'attempt_counter',
        'user_id',
        'created_by',
        'deleted_by',
    ];

    protected static function boot(): void
    {
        parent::boot();
        self::creating(function($model) {
            if (is_null($model->expires_at)) {
                $seconds = config('cuztomisable.account.registration.expires_in', 300);
                $model->expires_at = now()->addSeconds($seconds);
            }
            $model->code = generateCode(
                config('cuztomisable.account.registration.length', 6),
                'cuztomisable'
            );
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function expiredAgo(): Attribute
    {
        return Attribute::make(
            get: function () {
                $expiresAt = Carbon::parse($this->expires_at)->setTimezone('UTC');
                $difference = Carbon::now('UTC')->diffInSeconds($expiresAt, false);
                if (!$this->expires_at || $difference > 0) {
                    return null;
                }
                return Carbon::parse($expiresAt)->diffForHumans();
            },
        );
    }

    public function resendIn(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $resendAfter = config('cuztomisable.account.registration.resend_after', 300);
                $sentAt = Carbon::parse($value)->setTimezone('UTC');
                $difference = round(Carbon::now('UTC')->diffInSeconds($sentAt, false));
                if ($difference >= 0) {
                    return 0;
                }
                $timeLeft = $resendAfter + $difference;
                return $timeLeft > 0 ? ceil($timeLeft) : 0;
            },
        );
    }

}

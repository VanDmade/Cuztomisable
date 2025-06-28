<?php

namespace VanDmade\Cuztomisable\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Carbon\Carbon;

class Registration extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'user_registrations';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'code',
        'used_at',
        'sent_at',
        'user_id',
        'expires_at',
        'created_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'code',
        'user_id',
        'created_by',
        'deleted_by',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function($model) {
            $model->created_by = Auth::user()->id;
            if (is_null($model->expires_at)) {
                $seconds = config('cuztomisable.account.registration.expires_in', 300);
                $model->expires_at = date('Y-m-d H:i:s', strtotime('+'.$seconds.' seconds'));
            }
            $model->code = generateCode(
                config('cuztomisable.account.registration.length', 6),
                'cuztomisable'
            );
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getExpiredAgoAttribute(): ?string
    {
        $expiresAt = Carbon::parse($this->expires_at)->setTimezone('UTC');
        $difference = Carbon::now('UTC')->diffInSeconds($expiresAt, false);
        if (!$this->expires_at || $difference > 0) {
            return null;
        }
        return Carbon::parse($expiresAt)->diffForHumans();
    }

    public function getResendInAttribute($value)
    {
        $resendAfter = config('cuztomisable.account.registration.resend_after', 300);
        $sentAt = Carbon::parse($value)->setTimezone('UTC');
        $difference = round(Carbon::now('UTC')->diffInSeconds($sentAt, false));
        if ($difference >= 0) {
            return 0;
        }
        $timeLeft = $resendAfter + $difference;
        return $timeLeft > 0 ? ceil($timeLeft) : 0;
    }

}

<?php

namespace VanDmade\Cuztomisable\Models\Users\Passwords;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use VanDmade\Cuztomisable\Enums\SentVia;
use VanDmade\Cuztomisable\Traits\Concerns\SoftDeletes;

class Reset extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'user_password_resets';

    protected $fillable = [
        'code',
        'token',
        'expires_at',
        'sent_at',
        'sent_via',
        'used_at',
        'attempt_counter',
        'user_id',
        'created_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'used_at' => 'datetime',
        'deleted_at' => 'datetime',
        'sent_via' => SentVia::class,
    ];

    protected $hidden = [
        'code',
        'user_id',
        'created_by',
        'deleted_by',
    ];

    protected static function boot(): void
    {
        parent::boot();
        self::creating(function($model) {
            $model->created_by = Auth::id();
            $codeLength = (int) config('cuztomisable.account.code.length', 6);
            $tokenLength = (int) config('cuztomisable.account.token_length', 16);
            $model->code = self::generateNumericCode($codeLength);
            $model->token = Str::random($tokenLength);
            if (is_null($model->expires_at)) {
                $seconds = config('cuztomisable.account.code.expires_in', 300);
                $model->expires_at = now()->addSeconds($seconds);
            }
        });
    }

    public static function generateNumericCode(int $length): string
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= (string) random_int(0, 9);
        }
        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'deleted_by');
    }

}

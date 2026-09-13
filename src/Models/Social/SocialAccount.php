<?php

namespace VanDmade\Cuztomisable\Models\Social;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A linked third-party social provider account (e.g. Google, Facebook) for a user.
 */
class SocialAccount extends Model
{

    use HasFactory;

    protected $table = 'social_accounts';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'user_id',
        'token',
        'refresh_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

}

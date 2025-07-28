<?php

namespace VanDmade\Cuztomisable\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use VanDmade\Cuztomisable\Models\Users\User;

class RefreshToken extends Model
{

    use HasFactory;

    protected $table = 'personal_refresh_tokens';

    protected $fillable = [
        'token',
        'expires_at',
        'used_at',
        'revoked',
        'user_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'revoked' => 'boolean',
    ];

    protected $hidden = [
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}

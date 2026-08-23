<?php

namespace VanDmade\Cuztomisable\Models\Organizations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Model
{

    use HasFactory;

    protected $table = 'organization_user';

    protected $fillable = [
        'user_id',
        'organization_id',
    ];

    protected $hidden = [
        'user_id',
        'organization_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

}

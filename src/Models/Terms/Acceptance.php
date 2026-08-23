<?php

namespace VanDmade\Cuztomisable\Models\Terms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Acceptance extends Model
{

    protected $table = 'terms_acceptances';

    protected $fillable = [
        'user_id',
        'terms_and_conditions_id',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    protected $hidden = [
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function terms(): BelongsTo
    {
        return $this->belongsTo(TermsAndConditions::class, 'terms_and_conditions_id');
    }

}

<?php

namespace VanDmade\Cuztomisable\Models\Terms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VanDmade\Cuztomisable\Concerns\Auditable;

class TermsAndConditions extends Model
{

    use Auditable;

    protected $table = 'terms_and_conditions';

    protected $fillable = [
        'version',
        'content',
        'published_at',
        'published_by',
        'requires_reacceptance',
        'created_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'requires_reacceptance' => 'boolean',
    ];

    protected $hidden = [
        'created_by',
        'published_by',
    ];

    public function acceptances(): HasMany
    {
        return $this->hasMany(Acceptance::class, 'terms_and_conditions_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'published_by');
    }

}

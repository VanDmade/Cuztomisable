<?php

namespace VanDmade\Cuztomisable\Models\Organizations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use VanDmade\Cuztomisable\Enums\Organizations\Role;

/**
 * A user's membership in an organization. Used as a BelongsToMany::using() pivot model (see
 * BelongsToOrganizations::organizations()), which requires AsPivot for hydration
 * (Model::fromRawAttributes() alone doesn't exist without it).
 */
class User extends Model
{

    use HasFactory, AsPivot;

    protected $table = 'organization_user';

    protected $fillable = [
        'user_id',
        'organization_id',
        'role',
    ];

    protected $casts = [
        'role' => Role::class,
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

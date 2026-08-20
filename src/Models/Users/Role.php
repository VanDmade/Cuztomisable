<?php

namespace VanDmade\Cuztomisable\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VanDmade\Cuztomisable\Concerns\Auditable;
use VanDmade\Cuztomisable\Concerns\SoftDeletes;
use VanDmade\Cuztomisable\Models\Roles;

class Role extends Model
{

    use HasFactory, Auditable, SoftDeletes;

    protected $table = 'user_roles';

    protected $fillable = [
        'user_id',
        'role_id',
        'created_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'user_id',
        'role_id',
        'created_by',
        'deleted_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Roles\Role::class, 'role_id');
    }

}

<?php

namespace VanDmade\Cuztomisable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use VanDmade\Cuztomisable\Concerns\Auditable;
use VanDmade\Cuztomisable\Concerns\SoftDeletes;
use VanDmade\Cuztomisable\Models\Roles;

class Permission extends Model
{

    use HasFactory, Auditable, SoftDeletes;

    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'created_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'created_by',
        'deleted_by',
    ];

    public function roles(): HasManyThrough
    {
        return $this->hasManyThrough(
            Roles\Role::class,
            Roles\Permission::class,
            'permission_id',
            'id',
            'id',
            'role_id',
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(config('auth.providers.users.model'), 'user_permissions');
    }

}

<?php

namespace VanDmade\Cuztomisable\Models\Roles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use VanDmade\Cuztomisable\Concerns\Auditable;
use VanDmade\Cuztomisable\Concerns\HasOrganization;
use VanDmade\Cuztomisable\Concerns\SoftDeletes;
use VanDmade\Cuztomisable\Models\Permission as PermissionModel;

class Role extends Model
{

    use HasFactory, Auditable, SoftDeletes, HasOrganization;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'organization_id',
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

    public function permissions(): HasManyThrough
    {
        return $this->hasManyThrough(
            PermissionModel::class,
            Permission::class,
            'role_id',
            'id',
            'id',
            'permission_id',
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(config('auth.providers.users.model'), 'user_roles');
    }

    public function permissionLinks(): HasMany
    {
        return $this->hasMany(Permission::class, 'role_id');
    }

}

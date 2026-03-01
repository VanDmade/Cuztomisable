<?php

namespace VanDmade\Cuztomisable\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use VanDmade\Cuztomisable\Traits\Concerns\SoftDeletes;
use VanDmade\Cuztomisable\Models\Roles;
use Illuminate\Support\Facades\Auth;

class Permission extends Model
{

    use HasFactory, SoftDeletes;

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

    public static function boot(): void
    {
        parent::boot();
        self::creating(function($model) {
            $model->created_by = Auth::check() ? Auth::user()->id : null;
        });
    }

    public function roles(): HasManyThrough
    {
        return $this->hasManyThrough(
            Roles\Permission::class,
            Roles\Role::class,
            'id',
            'role_id',
            'permission_id',
            'id',
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(config('auth.providers.users.model'), 'user_permissions');
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

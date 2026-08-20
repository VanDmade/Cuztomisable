<?php

namespace VanDmade\Cuztomisable\Models\Roles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VanDmade\Cuztomisable\Concerns\Auditable;
use VanDmade\Cuztomisable\Concerns\SoftDeletes;
use VanDmade\Cuztomisable\Models\Permission as PermissionModel;

class Permission extends Model
{

    use HasFactory, Auditable, SoftDeletes;

    protected $table = 'role_permissions';

    protected $fillable = [
        'role_id',
        'permission_id',
        'created_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'role_id',
        'permission_id',
        'created_by',
        'deleted_by',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(PermissionModel::class, 'permission_id');
    }

}

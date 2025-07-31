<?php

namespace VanDmade\Cuztomisable\Models\Roles;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VanDmade\Cuztomisable\Models\Permission as PermissionModel;
use Auth;

class Permission extends Model
{

    use HasFactory, SoftDeletes;

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

    public static function boot()
    {
        parent::boot();
        self::creating(function($model) {
            $model->created_by = Auth::check() ? Auth::user()->id : null;
        });
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function permission()
    {
        return $this->belongsTo(PermissionModel::class, 'permission_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'deleted_by');
    }

}

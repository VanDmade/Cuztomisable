<?php

namespace VanDmade\Cuztomisable\Models\Roles;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VanDmade\Cuztomisable\Models\Users\User;
use VanDmade\Cuztomisable\Models\Permission as PermissionModel;
use Auth;

class Role extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'roles';

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

    public static function boot()
    {
        parent::boot();
        self::creating(function($model) {
            $model->created_by = Auth::check() ? Auth::user()->id : null;
        });
    }

    public function permissions()
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

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    public function permissionLinks()
    {
        return $this->hasMany(Permission::class, 'role_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

}

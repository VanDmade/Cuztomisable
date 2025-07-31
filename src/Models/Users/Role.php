<?php

namespace VanDmade\Cuztomisable\Models\Users;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VanDmade\Cuztomisable\Models\Roles;
use Auth;

class Role extends Model
{

    use HasFactory, SoftDeletes;

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

    public static function boot()
    {
        parent::boot();
        self::creating(function($model) {
            $model->created_by = Auth::check() ? Auth::user()->id : null;
        });
        self::deleting(function($model) {
            $model->deleted_by = Auth::check() ? Auth::user()->id : null;
            $model->deleted_at = date('Y-m-d H:i:s');
        });
    }

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Roles\Role::class, 'role_id');
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

<?php

namespace VanDmade\Cuztomisable\Models\Users;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VanDmade\Cuztomisable\Traits\Concerns\SoftDeletes;
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

    public static function boot(): void
    {
        parent::boot();
        self::creating(function($model) {
            $model->created_by = Auth::check() ? Auth::user()->id : null;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Roles\Role::class, 'role_id');
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

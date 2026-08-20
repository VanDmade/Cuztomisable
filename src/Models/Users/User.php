<?php

namespace VanDmade\Cuztomisable\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use VanDmade\Cuztomisable\Concerns\Auditable;
use VanDmade\Cuztomisable\Concerns\CuztomisableUser;
use VanDmade\Cuztomisable\Concerns\SoftDeletes;

/**
 * The default concrete User model Cuztomisable ships - table/fillable/casts only. All the
 * actual behavior (login rules, permissions, relationships) lives in CuztomisableUser, so a
 * host app can compose the same trait into its own User model instead of using this one.
 */
class User extends Authenticatable
{

    use HasFactory, HasApiTokens, SoftDeletes, Auditable, CuztomisableUser;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'username',
        'email',
        'email_verified_at',
        'disable_emails',
        'password',
        'timezone',
        'token',
        'locked',
        'change_password',
        'change_password_sent_at',
        'multi_factor_authentication',
        'admin',
        'attempts',
        'attempt_timer',
        'created_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'disable_emails' => 'boolean',
        'locked' => 'boolean',
        'change_password' => 'boolean',
        'change_password_sent_at' => 'datetime',
        'multi_factor_authentication' => 'boolean',
        'admin' => 'boolean',
        'attempt_timer' => 'datetime',
    ];

    protected $hidden = [
        'attempts',
        'attempt_timer',
        'created_by',
        'deleted_by',
    ];

    public static function boot(): void
    {
        parent::boot();
        self::creating(function($model) {
            $model->token = generateCode(8);
        });
    }

}

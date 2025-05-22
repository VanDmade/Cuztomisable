<?php

namespace VanDmade\Cuztomisable\Models\Users\Passwords;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VanDmade\Cuztomisable\Models\Users\User;
use Auth;

class Reset extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'user_password_resets';

    protected $fillable = [
        'code',
        'token',
        'expires_at',
        'sent_at',
        'sent_via',
        'used_at',
        'attempt_counter',
        'user_id',
        'created_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'used_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'code',
        'user_id',
        'created_by',
        'deleted_by',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function($model) {
            $model->created_by = Auth::check() ? Auth::user()->id : null;
            $model->code = generateCode(config('cuztomisable.account.code.length', 6), 'cuztomisable', $model->id);
            $model->token = generateCode(config('cuztomisable.account.token_length', 16), 'cuztomisable', $model->id);
            if (is_null($model->expires_at)) {
                $seconds = config('cuztomisable.account.code.expires_in', 300);
                $model->expires_at = date('Y-m-d H:i:s', strtotime('+'.$seconds.' seconds'));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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

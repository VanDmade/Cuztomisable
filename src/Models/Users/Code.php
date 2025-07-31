<?php

namespace VanDmade\Cuztomisable\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class Code extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'user_codes';

    protected $fillable = [
        'code',
        'token',
        'expires_at',
        'sent_at',
        'sent_via',
        'used_at',
        'user_id',
        'user_ip_address_id',
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
        'user_ip_address_id',
        'deleted_by',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function($model) {
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
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function ipAddress()
    {
        return $this->belongsTo(IpAddress::class, 'user_ip_address_id');
    }

}

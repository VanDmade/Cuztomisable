<?php

namespace VanDmade\Cuztomisable\Models\Logs;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use VanDmade\Cuztomisable\Models\Users;
use Auth;

class Error extends Model
{

    use HasFactory;

    protected $table = 'error_logs';

    protected $fillable = [
        'user_id',
        'message',
        'line',
        'file',
        'code',
        'debug_code',
        'parameters',
    ];

    protected $casts = [];

    protected $hidden = [
        'user_id',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function($model) {
            if (!Auth::check()) {
                // If the user is not logged in the system will try and find the user based on the IP Address
                $ipAddress = Users\IpAddress::where('ip_address', '=', getIpAddress())->first();
            }
            $model->user_id = Auth::check() ? Auth::user()->id : ($ipAddress->user_id ?? null);
            $model->debug_code = generateCode(6, 'debug', 'DB');
            // Adds the IP Address Used parameter
            $parameters = $model->parameters;
            $parameters['ip_address_used'] = isset($ipAddress->id) ? true : false;
            $model->parameters = $parameters;
        });
    }

    public function parameters(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => json_decode($value) ? json_decode($value, true) : [],
            set: fn (array $value) => !is_null($value) ? json_encode($value) : null
        );
    }

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

}

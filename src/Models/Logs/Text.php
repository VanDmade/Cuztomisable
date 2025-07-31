<?php

namespace VanDmade\Cuztomisable\Models\Logs;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use VanDmade\Cuztomisable\Models\Users;
use Auth;

class Text extends Model
{

    use HasFactory;

    protected $table = 'text_logs';

    protected $fillable = [
        'country_code',
        'number',
        'message',
        'parameters',
        'created_by',
    ];

    protected $casts = [];

    protected $hidden = [
        'created_by',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function($model) {
            if (Auth::check()) {
                $model->created_by = Auth::user()->id;
            }
        });
    }

    public function parameters(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => json_decode($value) ? json_decode($value, true) : [],
            set: fn (string|array $value) => !is_null($value) ? (is_array($value) ? json_encode($value) : $value) : null
        );
    }

    public function createdBy()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

}
<?php

namespace VanDmade\Cuztomisable\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use VanDmade\Cuztomisable\Models\Users\User;
use Auth;

class Formora extends Model
{

    use HasFactory;

    protected $table = 'formora';

    protected $fillable = [
        'to',
        'to_params',
        'current',
        'current_params',
        'form',
        'user_id',
    ];

    protected $casts = [];

    protected $hidden = [
        'user_id',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function($model) {
            $model->user_id = Auth::check() ? Auth::user()->id : null;
        });
    }

    public function toParams(): Attribute
    {
        return Attribute::make(get: fn () => json_decode($this->attributes['to_params'] ?? null, true));
    }

    public function currentParams(): Attribute
    {
        return Attribute::make(get: fn () => json_decode($this->attributes['current_params'] ?? null, true));
    }

    public function form(): Attribute
    {
        return Attribute::make(get: fn () => json_decode($this->attributes['form'] ?? null, true));
    }

}

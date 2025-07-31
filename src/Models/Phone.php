<?php

namespace VanDmade\Cuztomisable\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VanDmade\Cuztomisable\Models\Users;
use Auth;

class Phone extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'phones';

    protected $fillable = [
        'number',
        'country_code',
        'extension',
        'mobile',
        'default',
        'disable_messages',
        'verified_at',
        'user_id',
        'created_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'mobile' => 'boolean',
        'default' => 'boolean',
        'disable_messages' => 'boolean',
        'verified_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'user_id',
        'created_by',
        'deleted_by',
    ];

    protected $appends = ['full_phone_number']; 

    public static function boot()
    {
        parent::boot();
        self::creating(function($model) {
            $model->created_by = Auth::check() ? Auth::user()->id : null;
        });
    }

    public function number(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => cleanPhone($value)
        );
    }

    public function fullPhoneNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => '+'.$this->country_code.' '.$this->number.
                (!is_null($this->extension) ? (' ext '.$this->extension) : '')
        );
    }

    public function obscuredNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => substr_replace($this->number, '******', 0, strlen($this->number) - 4)
        );
    }

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
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

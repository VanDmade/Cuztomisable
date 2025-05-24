<?php

namespace VanDmade\Cuztomisable\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use VanDmade\Cuztomisable\Models\Users\User;
use Auth;

class Address extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'addresses';

    protected $fillable = [
        'address',
        'address_two',
        'address_three',
        'state_or_province',
        'city',
        'country',
        'zip_or_postal_code',
        'shipping',
        'billing',
        'default',
        'user_id',
        'created_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'shipping' => 'boolean',
        'billing' => 'boolean',
        'default' => 'boolean',
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

    public static function boot()
    {
        parent::boot();
        self::creating(function($model) {
            $model->created_by = Auth::check() ? Auth::user()->id : null;
        });
    }

    public function fullAddress(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->address.
                (!is_null($this->address_two) ? (' '.$this->address_two) : '').
                (!is_null($this->address_three) ? (' '.$this->address_three) : '').
                (!is_null($this->city) ? (', '.$this->city) : '').
                ' '.$this->state_or_province.' '.$this->zip_or_postal_code.', '.$this->country
        );
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

<?php

namespace VanDmade\Cuztomisable\Models\Organizations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use VanDmade\Cuztomisable\Concerns\SoftDeletes;
use VanDmade\Cuztomisable\Models\Address;
use VanDmade\Cuztomisable\Models\Phone;

class Organization extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'organizations';

    protected $fillable = [
        'name',
        'slug',
        'deleted_at',
    ];

    public function phones(): HasMany
    {
        return $this->hasMany(Phone::class, 'organization_id');
    }

    public function defaultPhone(): HasOne
    {
        return $this->hasOne(Phone::class, 'organization_id')
            ->where('default', '=', true);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'organization_id');
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class, 'organization_id')
            ->where('default', '=', true);
    }

}

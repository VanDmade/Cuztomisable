<?php

namespace VanDmade\Cuztomisable\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use VanDmade\Cuztomisable\Models\Address;
use VanDmade\Cuztomisable\Models\Phone;

/**
 * The standard set of relationships a Cuztomisable-managed organization needs - meant to be
 * composed into a host app's own Organization model (which owns its table/fillable/casts), not
 * just the concrete Models\Organizations\Organization Cuztomisable ships by default.
 */
trait CuztomisableOrganization
{

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

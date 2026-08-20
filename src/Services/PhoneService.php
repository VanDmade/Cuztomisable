<?php

namespace VanDmade\Cuztomisable\Services;

use Illuminate\Database\Eloquent\Model;
use VanDmade\Cuztomisable\Models\Phone;

/**
 * Orchestration for a user's phone numbers - currently just the single default phone. Shared
 * between RegistrationService and (eventually) UserService rather than duplicated - a brand new
 * user has no existing default, so this naturally creates one; an existing user's call updates
 * the one they already have. Same operation either way.
 */
class PhoneService
{

    public function setDefault(Model $user, string $number, mixed $countryCode = null): Phone
    {
        $phone = $user->defaultPhone ?? new Phone([
            'user_id' => $user->id,
            'default' => true,
        ]);
        $phone->number = $number;
        $phone->country_code = $countryCode ?? config('cuztomisable.locations.default_country_code', 1);
        $phone->save();
        return $phone;
    }

}

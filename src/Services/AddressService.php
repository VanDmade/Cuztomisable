<?php

namespace VanDmade\Cuztomisable\Services;

use Illuminate\Database\Eloquent\Model;
use VanDmade\Cuztomisable\Models\Address;

/**
 * Orchestration for a user's addresses - currently just the single default address. Shared
 * between RegistrationService and (eventually) UserService rather than duplicated - same
 * reasoning as PhoneService (create-or-update-the-default is one operation either way).
 */
class AddressService
{

    public function setDefault(Model $user, array $data): Address
    {
        $address = $user->defaultAddress ?? new Address([
            'user_id' => $user->id,
            'default' => true,
        ]);
        $address->address = $data['address'];
        $address->address_two = $data['address_two'] ?? null;
        $address->address_three = $data['address_three'] ?? null;
        $address->state_or_province = $data['state_or_province'];
        $address->city = $data['city'];
        $address->country = $data['country'];
        $address->zip_or_postal_code = $data['zip_or_postal_code'];
        $address->save();
        return $address;
    }

}

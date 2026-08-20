<?php

namespace VanDmade\Cuztomisable\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after RegistrationService::save() creates a new user account - $user is typed as
 * the generic Model base, not a concrete Cuztomisable class, since auth.providers.users.model
 * is host-app-configurable.
 */
class UserRegistered
{

    use Dispatchable;

    public function __construct(public readonly Model $user)
    {
    }

}

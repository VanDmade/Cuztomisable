<?php

namespace VanDmade\Cuztomisable\Events;

use Illuminate\Foundation\Events\Dispatchable;
use VanDmade\Cuztomisable\Models\Users\Registration;

/**
 * Dispatched after RegistrationService::invite() creates a new pending invite.
 */
class UserInvited
{

    use Dispatchable;

    public function __construct(public readonly Registration $registration)
    {
    }

}

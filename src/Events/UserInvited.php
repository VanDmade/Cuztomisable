<?php

namespace VanDmade\Cuztomisable\Events;

use Illuminate\Foundation\Events\Dispatchable;
use VanDmade\Cuztomisable\Models\Users\Registration;

/**
 * When a user is invited this event is dispatched
 */
class UserInvited
{

    use Dispatchable;

    public function __construct(public readonly Registration $registration)
    {
    }

}

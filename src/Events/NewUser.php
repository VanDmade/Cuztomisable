<?php

namespace VanDmade\Cuztomisable\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when a user is created within the system
 */
class NewUser
{

    use Dispatchable;

    public function __construct(public readonly Model $user)
    {
    }

}

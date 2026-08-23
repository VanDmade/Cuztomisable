<?php

namespace VanDmade\Cuztomisable\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use VanDmade\Cuztomisable\Models\Users\IpAddress;

/**
 * When the user logs into the system from a new IP address, this event is dispatched.
 */
class NewIpAddressLogin
{

    use Dispatchable;

    public function __construct(
        public readonly Model $user,
        public readonly IpAddress $ipAddress
    ) {
    }

}

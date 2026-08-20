<?php

namespace VanDmade\Cuztomisable\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class UserRegistered
{

    use Dispatchable;

    public function __construct(public readonly Model $user)
    {
    }

}

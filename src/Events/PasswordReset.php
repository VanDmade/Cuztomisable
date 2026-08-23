<?php

namespace VanDmade\Cuztomisable\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use VanDmade\Cuztomisable\Models\Users\Passwords\Reset;

/**
 * When a user resets their password this event is dispatched
 */
class PasswordReset
{

    use Dispatchable;

    public function __construct(
        public readonly Model $user,
        public readonly Reset $reset
    ) {
    }

}

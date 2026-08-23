<?php

namespace VanDmade\Cuztomisable\Events;

use Illuminate\Foundation\Events\Dispatchable;
use VanDmade\Cuztomisable\Mail\VanDmadeMailable;

/**
 * When an email is sent this event is dispatched
 */
class EmailSent
{

    use Dispatchable;

    public function __construct(public readonly VanDmadeMailable $mailable)
    {
    }

}

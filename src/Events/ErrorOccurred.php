<?php

namespace VanDmade\Cuztomisable\Events;

use Illuminate\Foundation\Events\Dispatchable;
use VanDmade\Cuztomisable\Models\Logs\Error;

/**
 * Whenever an error is created within Cuztomisable this event will be dispatched
 */
class ErrorOccurred
{

    use Dispatchable;

    public function __construct(public readonly Error $error)
    {
    }

}

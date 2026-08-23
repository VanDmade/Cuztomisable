<?php

namespace VanDmade\Cuztomisable\Events;

use Illuminate\Foundation\Events\Dispatchable;
use VanDmade\Cuztomisable\Models\Terms\TermsAndConditions;

/**
 * When a new Terms and Conditions is published this event is dispatched
 */
class TermsPublished
{

    use Dispatchable;

    public function __construct(public readonly TermsAndConditions $terms)
    {
    }

}

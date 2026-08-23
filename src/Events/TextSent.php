<?php

namespace VanDmade\Cuztomisable\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * When a text message is sent this event is dispatched
 */
class TextSent
{

    use Dispatchable;

    public function __construct(
        public readonly string $countryCode,
        public readonly string $number,
        public readonly string $message,
        public readonly ?string $cleanedPhone = null,
        public readonly bool $debug = false,
        public readonly ?int $createdBy = null
    ) {
    }

}

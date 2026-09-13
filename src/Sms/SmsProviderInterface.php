<?php

namespace VanDmade\Cuztomisable\Sms;

/**
 * Contract every SMS provider implementation must follow.
 */
interface SmsProviderInterface
{

    public function send(string $countryCode, string $number, string $message): bool;

}

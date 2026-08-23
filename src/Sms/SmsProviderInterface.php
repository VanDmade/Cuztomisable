<?php

namespace VanDmade\Cuztomisable\Sms;

interface SmsProviderInterface
{

    public function send(string $countryCode, string $number, string $message): bool;

}

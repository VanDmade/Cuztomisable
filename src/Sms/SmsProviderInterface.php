<?php

namespace VanDmade\Cuztomisable\Sms;

/**
 * Sends a single SMS message. Kept as a real interface (unlike ImageService) because SMS
 * providers are a genuine structural fork - swapping AWS SNS for Twilio, Vonage, etc. changes
 * the whole client/API shape, not just a config parameter.
 */
interface SmsProviderInterface
{

    public function send(string $countryCode, string $number, string $message): bool;

}

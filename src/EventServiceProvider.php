<?php

namespace VanDmade\Cuztomisable;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use SocialiteProviders\Instagram\InstagramExtendSocialite;
use SocialiteProviders\LinkedIn\LinkedInExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Twitter\TwitterExtendSocialite;
use VanDmade\Cuztomisable\Events\TextSent;
use VanDmade\Cuztomisable\Listeners\LogEmail;
use VanDmade\Cuztomisable\Listeners\LogText;
use VanDmade\Cuztomisable\Listeners\PreventDefaultAdminEmail;

/**
 * Wires up Cuztomisable's event listeners.
 */
class EventServiceProvider extends ServiceProvider
{

    protected $listen = [
        MessageSending::class => [
            // Cancels sending if the recipient is the default admin placeholder address
            PreventDefaultAdminEmail::class,
        ],
        MessageSent::class => [
            // Logs the email details
            LogEmail::class,
        ],
        TextSent::class => [
            // Logs the text message details
            LogText::class,
        ],
        // Registers the extra providers Socialite doesn't ship with by default
        SocialiteWasCalled::class => [
            LinkedInExtendSocialite::class.'@handle',
            TwitterExtendSocialite::class.'@handle',
            InstagramExtendSocialite::class.'@handle',
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }

}
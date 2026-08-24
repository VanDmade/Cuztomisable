<?php

namespace VanDmade\Cuztomisable;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use VanDmade\Cuztomisable\Events\TextSent;
use VanDmade\Cuztomisable\Listeners\LogEmail;
use VanDmade\Cuztomisable\Listeners\LogText;
use VanDmade\Cuztomisable\Listeners\PreventDefaultAdminEmail;

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
    ];

    public function boot(): void
    {
        parent::boot();
    }

}
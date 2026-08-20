<?php

namespace VanDmade\Cuztomisable;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSent;
use VanDmade\Cuztomisable\Events\TextSent;
use VanDmade\Cuztomisable\Listeners\LogEmail;
use VanDmade\Cuztomisable\Listeners\LogText;

class EventServiceProvider extends ServiceProvider
{

    protected $listen = [
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
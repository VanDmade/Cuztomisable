<?php

namespace VanDmade\Cuztomisable;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSent;
use VanDmade\Cuztomisable\Listeners\LogEmail;

class EventServiceProvider extends ServiceProvider
{

    protected $listen = [
        MessageSent::class => [
            // Logs the email details
            LogEmail::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }

}
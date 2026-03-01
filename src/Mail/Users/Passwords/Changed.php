<?php

namespace VanDmade\Cuztomisable\Mail\Users\Passwords;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Mail\VanDmadeMailable;

class Changed extends VanDmadeMailable implements ShouldQueue
{

    use Queueable, SerializesModels;

    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return $this->defaultEnvelope(__('cuztomisable/user.emails.subjects.changed'));
    }

    public function content(): Content
    {
        return new Content(
            view: config('cuztomisable.account.notifications.changed.view'),
            with: [
                'user' => $this->user,
                'logo' => asset(config('cuztomisable.account.emails.logo', 'images/logo.png')),
                'company' => config('app.name'),
            ],
        );
    }

}

<?php

namespace VanDmade\Cuztomisable\Mail\Authentication\Passwords;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Models\Users\Passwords\Reset;
use VanDmade\Cuztomisable\Mail\VanDmadeMailable;

class Forgot extends VanDmadeMailable implements ShouldQueue
{

    use Queueable, SerializesModels;

    private Reset $reset;

    public function __construct(Reset $reset)
    {
        $this->reset = $reset;
    }

    public function envelope(): Envelope
    {
        return $this->defaultEnvelope(__('cuztomisable/authentication.emails.subjects.forgot'));
    }

    public function content(): Content
    {
        return new Content(
            view: config('cuztomisable.account.notifications.forgot.view'),
            with: [
                'user' => $this->reset->user,
                'code' => $this->reset->code,
                'logo' => asset(config('cuztomisable.account.emails.logo', 'images/logo.png')),
                'company' => config('app.name'),
            ],
        );
    }

}

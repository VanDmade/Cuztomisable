<?php

namespace VanDmade\Cuztomisable\Mail\Users\Passwords;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Changed extends Mailable
{

    use Queueable, SerializesModels;

    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('cuztomisable/user.emails.subjects.changed'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: config('cuztomisable.account.notifications.changed.view'),
            with: [
                'user' => $this->user,
                'logo' => asset('images/logo.png'),
                'company' => env('APP_NAME'),
            ],
        );
    }

}

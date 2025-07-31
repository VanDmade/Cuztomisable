<?php

namespace VanDmade\Cuztomisable\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Support extends Mailable
{

    use Queueable, SerializesModels;

    private $user, $text;

    public function __construct($user, $text)
    {
        $this->user = $user;
        $this->text = $text;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('cuztomisable/user.emails.subjects.support'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: config('cuztomisable.account.notifications.support.view'),
            with: [
                'user' => $this->user,
                'logo' => asset('images/logo.png'),
                'company' => env('APP_NAME'),
                'text' => $this->text,
            ],
        );
    }

}

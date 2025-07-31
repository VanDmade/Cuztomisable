<?php

namespace VanDmade\Cuztomisable\Mail\Authentication\Passwords;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Models\Users\Passwords\Reset as ResetModel;

class Reset extends Mailable
{

    use Queueable, SerializesModels;

    private $user, $reset;

    public function __construct($user, ResetModel $reset)
    {
        $this->user = $user;
        $this->reset = $reset;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('cuztomisable/authentication.emails.subjects.reset'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: config('cuztomisable.account.notifications.reset.view'),
            with: [
                'user' => $this->user,
                'logo' => asset('images/logo.png'),
                'company' => env('APP_NAME'),
                'lockUrl' => url('/lock/'.implode('/', [
                    $this->user->id,
                    'reset',
                    $this->reset->id,
                    $this->reset->token,
                ])),
            ],
        );
    }

}

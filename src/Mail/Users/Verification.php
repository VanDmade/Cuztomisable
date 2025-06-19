<?php

namespace VanDmade\Cuztomisable\Mail\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Models\Users\User;

class Verification extends Mailable
{

    use Queueable, SerializesModels;

    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('cuztomisable/authentication.emails.subjects.verification'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: config('cuztomisable.account.notifications.email_verification.view'),
            with: [
                'user' => $this->user,
                'verificationUrl' => url('/verification/'.$this->user->token.'/email?email='.$this->user->email),
                'logo' => asset('images/logo.png'),
                'company' => env('APP_NAME'),
            ],
        );
    }

}

<?php

namespace VanDmade\Cuztomisable\Mail\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Mail\VanDmadeMailable;

class Verification extends VanDmadeMailable implements ShouldQueue
{

    use Queueable, SerializesModels;

    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return $this->defaultEnvelope(__('cuztomisable/email.subjects.email_verification'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'cuztomisable::authentication.verifications.email',
            with: [
                'user' => $this->user,
                'verificationUrl' => url('/verification/'.$this->user->token.'/email?email='.$this->user->email),
            ],
        );
    }

}

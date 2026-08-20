<?php

namespace VanDmade\Cuztomisable\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Mail\VanDmadeMailable;

class Support extends VanDmadeMailable implements ShouldQueue
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
        return $this->defaultEnvelope(__('cuztomisable/email.subjects.support'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'cuztomisable::support',
            with: [
                'user' => $this->user,
                'text' => $this->text,
            ],
        );
    }

}

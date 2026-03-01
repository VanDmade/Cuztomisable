<?php

namespace VanDmade\Cuztomisable\Mail;

use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Mail\Mailable as BaseMailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

abstract class VanDmadeMailable extends BaseMailable
{

    public function send($mailer)
    {
        // Initializes properties on the Swift Message object
        $this->withSymfonyMessage(function($message) {
            $message->template = get_class($this);
        });
        parent::send($mailer);
    }

    protected function defaultEnvelope(string $subject): Envelope
    {
        $fromConfig = config('cuztomisable.account.emails.from', []);
        $replyConfig = config('cuztomisable.account.emails.reply_to', []);
        $defaultName = config('app.name');
        $from = (!empty($fromConfig['address']))
            ? new Address($fromConfig['address'], $fromConfig['name'] ?? $defaultName)
            : null;
        $replyTo = (!empty($replyConfig['address']))
            ? [new Address($replyConfig['address'], $replyConfig['name'] ?? $defaultName)]
            : null;
        return new Envelope(
            subject: $subject,
            from: $from,
            replyTo: $replyTo,
        );
    }

}
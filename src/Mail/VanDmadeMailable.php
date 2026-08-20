<?php

namespace VanDmade\Cuztomisable\Mail;

use Illuminate\Mail\Mailable as BaseMailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Auth;
use VanDmade\Cuztomisable\Events\EmailSent;

abstract class VanDmadeMailable extends BaseMailable
{

    protected ?int $createdBy = null;

    public function queue($queue = null)
    {
        $this->createdBy ??= Auth::id();
        return parent::queue($queue);
    }

    public function send($mailer)
    {
        $this->createdBy ??= Auth::id();
        $this->withSymfonyMessage(function($message) {
            $message->template = get_class($this);
            $message->createdBy = $this->createdBy;
        });
        parent::send($mailer);
        EmailSent::dispatch($this);
    }

    protected function defaultEnvelope(string $subject): Envelope
    {
        $fromConfig = config('cuztomisable.notifications.emails.from', []);
        $replyConfig = config('cuztomisable.notifications.emails.reply_to', []);
        $defaultName = config('app.name');
        $from = (!empty($fromConfig['address'])) ?
            new Address($fromConfig['address'], $fromConfig['name'] ?? $defaultName) : null;
        $replyTo = (!empty($replyConfig['address'])) ?
            [new Address($replyConfig['address'], $replyConfig['name'] ?? $defaultName)] : null;
        return new Envelope(
            subject: $subject,
            from: $from,
            replyTo: $replyTo,
        );
    }

}
<?php

namespace VanDmade\Cuztomisable\Mail\Authentication;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Models\Users\Code;
use VanDmade\Cuztomisable\Mail\VanDmadeMailable;

class MFA extends VanDmadeMailable implements ShouldQueue
{

    use Queueable, SerializesModels;

    private $user, $code;

    public function __construct($user, Code $code)
    {
        $this->user = $user;
        $this->code = $code;
    }

    public function envelope(): Envelope
    {
        return $this->defaultEnvelope(__('cuztomisable/email.subjects.mfa'));
    }

    public function content(): Content
    {
        $expiresIn = null;
        if (!is_null($this->code->expires_at)) {
            $expiresIn = convertToTimeOutput(strtotime($this->code->expires_at) - time());
        }
        return new Content(
            view: 'cuztomisable::authentication.mfa',
            with: [
                'user' => $this->user,
                'code' => $this->code->code,
                'expires_in' => $expiresIn,
            ],
        );
    }

}

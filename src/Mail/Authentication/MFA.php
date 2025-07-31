<?php

namespace VanDmade\Cuztomisable\Mail\Authentication;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Models\Users\Code;

class MFA extends Mailable
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
        return new Envelope(
            subject: __('cuztomisable/authentication.emails.subjects.mfa'),
        );
    }

    public function content(): Content
    {
        $expiresIn = null;
        if (!is_null($this->code->expires_at)) {
            $expiresIn = convertToTimeOutput(strtotime($this->code->expires_at) - time());
        }
        return new Content(
            view: config('cuztomisable.login.notifications.mfa.view'),
            with: [
                'user' => $this->user,
                'logo' => asset('images/logo.png'),
                'company' => env('APP_NAME'),
                'code' => $this->code->code,
            ],
        );
    }

}

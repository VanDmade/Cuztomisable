<?php

namespace VanDmade\Cuztomisable\Mail\Users\Passwords;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Mail\VanDmadeMailable;

class Temporary extends VanDmadeMailable implements ShouldQueue
{

    use Queueable, SerializesModels;

    private $user, $password;

    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function envelope(): Envelope
    {
        return $this->defaultEnvelope(__('cuztomisable/user.emails.subjects.temporary'));
    }

    public function content(): Content
    {
        return new Content(
            view: config('cuztomisable.notifications.temporary.view'),
            with: [
                'user' => $this->user,
                'password' => $this->password,
                'logo' => asset(config('cuztomisable.notifications.emails.logo', 'images/logo.png')),
                'company' => config('app.name'),
            ],
        );
    }

}

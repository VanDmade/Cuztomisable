<?php

namespace VanDmade\Cuztomisable\Mail\Users\Passwords;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Models\Users\User;

class Temporary extends Mailable
{

    use Queueable, SerializesModels;

    private $user, $password;

    public function __construct(User $user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('cuztomisable/user.emails.subjects.temporary'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: config('cuztomisable.account.notifications.temporary.view'),
            with: [
                'user' => $this->user,
                'password' => $this->password,
                'logo' => asset('images/logo.png'),
                'company' => env('APP_NAME'),
            ],
        );
    }

}

<?php

namespace VanDmade\Cuztomisable\Mail\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Models\Users\Registration;
use Auth;

class Invitation extends Mailable
{

    use Queueable, SerializesModels;

    private $registration;

    public function __construct(Registration $registration)
    {
        $this->registration = $registration;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('cuztomisable/authentication.emails.subjects.invitation'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: config('cuztomisable.login.notifications.invitation.view'),
            with: [
                'sender' => Auth::check() ? Auth::user()->name : null,
                'name' => $this->registration->name,
                'url' => url('/registration/'.$this->registration->code),
                'expires' => config('cuztomisable.account.registration.expires_in', 300) / 60,
                'logo' => asset('images/logo.png'),
                'company' => env('APP_NAME'),
                'footer' => false,
            ],
        );
    }

}

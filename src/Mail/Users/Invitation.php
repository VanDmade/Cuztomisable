<?php

namespace VanDmade\Cuztomisable\Mail\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Models\Users\Registration;
use Illuminate\Support\Facades\Auth;
use VanDmade\Cuztomisable\Mail\VanDmadeMailable;

class Invitation extends VanDmadeMailable implements ShouldQueue
{

    use Queueable, SerializesModels;

    private $registration;

    public function __construct(Registration $registration)
    {
        $this->registration = $registration;
    }

    public function envelope(): Envelope
    {
        return $this->defaultEnvelope(__('cuztomisable/authentication.emails.subjects.invitation'));
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
                'logo' => asset(config('cuztomisable.account.emails.logo', 'images/logo.png')),
                'company' => config('app.name'),
                'footer' => false,
            ],
        );
    }

}

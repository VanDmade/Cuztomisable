<?php

namespace VanDmade\Cuztomisable\Mail\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Models\Users\IpAddress;
use VanDmade\Cuztomisable\Models\Users\User;

class NewIpAddress extends Mailable
{

    use Queueable, SerializesModels;

    private $user, $ip;

    public function __construct(User $user, IpAddress $ip)
    {
        $this->user = $user;
        $this->ip = $ip;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('cuztomisable/authentication.emails.subjects.new_ip_address'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: config('cuztomisable.login.notifications.new_ip_address.view'),
            with: [
                'user' => $this->user,
                'ip' => $this->ip,
                'logo' => asset('images/logo.png'),
                'company' => env('APP_NAME'),
            ],
        );
    }

}

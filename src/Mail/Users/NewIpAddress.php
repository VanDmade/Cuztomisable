<?php

namespace VanDmade\Cuztomisable\Mail\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Models\Users\IpAddress;
use VanDmade\Cuztomisable\Mail\VanDmadeMailable;

class NewIpAddress extends VanDmadeMailable implements ShouldQueue
{

    use Queueable, SerializesModels;

    private $user, $ip;

    public function __construct($user, IpAddress $ip)
    {
        $this->user = $user;
        $this->ip = $ip;
    }

    public function envelope(): Envelope
    {
        return $this->defaultEnvelope(__('cuztomisable/authentication.emails.subjects.new_ip_address'));
    }

    public function content(): Content
    {
        return new Content(
            view: config('cuztomisable.login.notifications.new_ip_address.view'),
            with: [
                'user' => $this->user,
                'ip' => $this->ip,
                'logo' => asset(config('cuztomisable.account.emails.logo', 'images/logo.png')),
                'company' => config('app.name'),
            ],
        );
    }

}

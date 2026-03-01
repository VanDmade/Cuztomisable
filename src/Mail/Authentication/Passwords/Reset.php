<?php

namespace VanDmade\Cuztomisable\Mail\Authentication\Passwords;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use VanDmade\Cuztomisable\Models\Users\Passwords\Reset as ResetModel;
use VanDmade\Cuztomisable\Mail\VanDmadeMailable;

class Reset extends VanDmadeMailable implements ShouldQueue
{

    use Queueable, SerializesModels;

    private $user, $reset;

    public function __construct($user, ResetModel $reset)
    {
        $this->user = $user;
        $this->reset = $reset;
    }

    public function envelope(): Envelope
    {
        return $this->defaultEnvelope(__('cuztomisable/authentication.emails.subjects.reset'));
    }

    public function content(): Content
    {
        return new Content(
            view: config('cuztomisable.account.notifications.reset.view'),
            with: [
                'user' => $this->user,
                'logo' => asset(config('cuztomisable.account.emails.logo', 'images/logo.png')),
                'company' => config('app.name'),
                'lockUrl' => url('/lock/'.implode('/', [
                    $this->user->id,
                    'reset',
                    $this->reset->id,
                    $this->reset->token,
                ])),
            ],
        );
    }

}

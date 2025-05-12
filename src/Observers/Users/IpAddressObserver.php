<?php

namespace VanDmade\Cuztomisable\Observers\Users;

use VanDmade\Cuztomisable\Models\Users\IpAddress;
use VanDmade\Cuztomisable\Models\Logs;
use VanDmade\Cuztomisable\Mail\Users\NewIpAddress as NewIpAddressMail;
use Mail;

class IpAddressObserver
{

    public function created(IpAddress $ipAddress): void
    {
        // Sends a notification to the user about the device
        Mail::to($ipAddress->user->email)
            ->send(new NewIpAddressMail($ipAddress->user, $ipAddress));
    }

    public function saved(IpAddress $ipAddress): void
    {
        // Logs that the user was logged into the app
        Logs\User::create([
            'user_id' => $ipAddress->user_id,
            'description' => 'user logged into the application with correct information',
            'parameters' => [
                'sent_to_mfa' => $ipAddress->requireMfa(),
                'user_ip_address' => $ipAddress->id,
            ],
        ]);
    }

}

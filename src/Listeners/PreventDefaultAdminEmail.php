<?php

namespace VanDmade\Cuztomisable\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;

class PreventDefaultAdminEmail
{

    public function handle(MessageSending $event): bool
    {
        $recipients = collect($event->message->getTo() ?? [])
            ->merge($event->message->getCc() ?? [])
            ->merge($event->message->getBcc() ?? []);

        foreach ($recipients as $recipient) {
            $domain = strtolower(strrchr($recipient->getAddress(), '@') ?: '');
            if ($domain === '@cuztomisable.com') {
                Log::info('Cuztomisable: skipped sending email to a reserved @cuztomisable.com placeholder address.', [
                    'to' => $recipient->getAddress(),
                    'subject' => $event->message->getSubject(),
                ]);
                return false;
            }
        }

        return true;
    }

}

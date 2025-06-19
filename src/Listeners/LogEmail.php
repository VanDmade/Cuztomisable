<?php

namespace VanDmade\Cuztomisable\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;
use VanDmade\Cuztomisable\Models\Logs;

class LogEmail
{

    private static $hidden = config('cuztomisable.account.emails.hidden_parameters', ['password']);

    public function handle(MessageSent $event): void
    {
        $sanitizedData = self::sanitizeSensitiveData($event->data ?? []);
        // Logs the email
        Logs\Email::create([
            'to' => self::getEmails($event->message->getTo()),
            'cc' => self::getEmails($event->message->getCc()),
            'bcc' => self::getEmails($event->message->getBcc()),
            'from' => self::getEmails($event->message->getFrom(), true),
            'subject' => $event->message->getSubject(),
            'parameters' => [
                'data' => $sanitizedData ?? null,
                'template' => $event->message->template ?? null,
            ],
        ]);
    }

    protected static function sanitizeSensitiveData(array $data, $depth = 0): array
    {
        if ($depth > 5) {
            return '**truncated**';
        }
        foreach ($data as $key => &$value) {
            if (in_array($key, self::$hidden)) {
                $value = '********';
            } elseif (is_array($value)) {
                // Recursive for nested arrays
                $value = self::sanitizeSensitiveData($value, $depth + 1);
            } elseif (isset($value->id)) {
                // Only the model's ID should be logged, not its full data.
                $value = $value->id;
            }
        }
        return $data;
    }

    protected function getEmails($array, $returnFirst = false)
    {
        $emails = collect($array)
            ->map(fn ($item) => $item->getAddress());
        return $returnFirst ? ($emails[0] ?? null) : $emails;
    }

}

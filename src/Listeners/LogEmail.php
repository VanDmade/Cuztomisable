<?php

namespace VanDmade\Cuztomisable\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;
use VanDmade\Cuztomisable\Models\Logs;

class LogEmail
{

    private const DEFAULT_HIDDEN_KEYS = [
        'password',
        'token',
        'secret',
        'code',
        'authorization',
        'api_key',
        'api-key',
        'access_token',
        'refresh_token',
        'client_secret',
        'session',
        'cookie',
        'otp',
    ];

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

    protected static function sanitizeSensitiveData(array $data, int $depth = 0): array
    {
        if ($depth > 5) {
            return ['__truncated__' => true];
        }
        $hidden = array_map('strtolower', array_merge(
            self::DEFAULT_HIDDEN_KEYS,
            config('cuztomisable.account.emails.hidden_parameters', [])
        ));
        foreach ($data as $key => &$value) {
            $keyName = is_string($key) ? strtolower($key) : $key;
            if (is_string($keyName) && in_array($keyName, $hidden, true)) {
                $value = '********';
            } elseif (is_array($value)) {
                // Recursive for nested arrays
                $value = self::sanitizeSensitiveData($value, $depth + 1);
            } elseif (is_object($value) && isset($value->id)) {
                // Only the model's ID should be logged, not its full data.
                $value = $value->id;
            } elseif (is_object($value)) {
                $value = get_class($value);
            }
        }
        return $data;
    }

    protected function getEmails($array, bool $returnFirst = false)
    {
        $emails = collect($array ?? [])
            ->map(fn ($item) => $item->getAddress());
        return $returnFirst ? ($emails[0] ?? null) : $emails;
    }

}

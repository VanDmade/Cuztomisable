<?php

namespace VanDmade\Cuztomisable\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;
use VanDmade\Cuztomisable\Services\Logs\EmailLogService;

class LogEmail
{

    public function __construct(
        protected readonly EmailLogService $emailLogService
    ) {
    }

    public function handle(MessageSent $event): void
    {
        // Creates a gate to prevent emails from being logged within Cuztomisable
        if (!config('cuztomisable.notifications.emails.log', true)) {
            return;
        }
        $sanitizedData = self::sanitizeSensitiveData($event->data ?? []);
        // Logs the email
        $this->emailLogService->create([
            'to' => self::getEmails($event->message->getTo()),
            'cc' => self::getEmails($event->message->getCc()),
            'bcc' => self::getEmails($event->message->getBcc()),
            'from' => self::getEmails($event->message->getFrom(), true),
            'subject' => $event->message->getSubject(),
            'created_by' => $event->message->createdBy ?? null,
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
        $hidden = array_map('strtolower', config('cuztomisable.notifications.emails.hidden_parameters', []));
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

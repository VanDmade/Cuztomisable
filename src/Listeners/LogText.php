<?php

namespace VanDmade\Cuztomisable\Listeners;

use VanDmade\Cuztomisable\Events\TextSent;
use VanDmade\Cuztomisable\Services\Logs\TextLogService;

class LogText
{

    public function __construct(
        protected readonly TextLogService $textLogService
    ) {
    }

    public function handle(TextSent $event): void
    {
        // Creates a gate to prevent texts from being logged within Cuztomisable
        if (!config('cuztomisable.notifications.texts.log', true)) {
            return;
        }
        $message = self::sanitizeMessage($event->message);
        $this->textLogService->create([
            'country_code' => $event->countryCode,
            'number' => $event->number,
            'message' => $message,
            'created_by' => $event->createdBy,
            'parameters' => [
                'cleaned_phone' => $event->cleanedPhone,
                'debug' => $event->debug,
                'redacted' => $message !== $event->message,
            ],
        ]);
    }

    protected static function sanitizeMessage(string $message): string
    {
        $redact = (bool) config('cuztomisable.notifications.texts.redact_message', false);
        if (!$redact) {
            return $message;
        }
        $replacement = (string) config('cuztomisable.notifications.texts.redact_replacement', '********');
        $patterns = config('cuztomisable.notifications.texts.redact_patterns', []);
        if (empty($patterns)) {
            return $replacement;
        }
        $redacted = preg_replace($patterns, $replacement, $message);
        return $redacted === null ? $replacement : $redacted;
    }

}

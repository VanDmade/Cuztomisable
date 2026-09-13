<?php

namespace VanDmade\Cuztomisable\Listeners;

use VanDmade\Cuztomisable\Events\TextSent;
use VanDmade\Cuztomisable\Services\Logs\TextLogService;

/**
 * Logs a sent text message to text_logs, truncating links/codes first.
 */
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
        if (config('cuztomisable.notifications.texts.redact_urls', true)) {
            $message = self::truncateUrls($message);
        }
        if (config('cuztomisable.notifications.texts.redact_codes', true)) {
            $message = self::truncateCodes($message);
        }
        if ((bool) config('cuztomisable.notifications.texts.redact_message', false)) {
            $replacement = (string) config('cuztomisable.notifications.texts.redact_replacement', '********');
            $patterns = config('cuztomisable.notifications.texts.redact_patterns', []);
            if (empty($patterns)) {
                return $replacement;
            }
            $redacted = preg_replace($patterns, $replacement, $message);
            $message = $redacted === null ? $replacement : $redacted;
        }
        return $message;
    }

    protected static function truncateUrls(string $message): string
    {
        return preg_replace_callback('/https?:\/\/\S+/i', function(array $match): string {
            $parts = parse_url($match[0]);
            if ($parts === false || empty($parts['host'])) {
                return '[link]';
            }
            $host = ($parts['scheme'] ?? 'http').'://'.$parts['host'];
            if (!empty($parts['port'])) {
                $host .= ':'.$parts['port'];
            }
            return $host.'/...';
        }, $message) ?? $message;
    }

    // Masks all but the first digit of any standalone 4-8 digit code (MFA codes, etc). The
    // negative lookbehind on ':' skips a port number left behind by truncateUrls() above (e.g.
    // "http://localhost:8000/...") rather than mangling it.
    protected static function truncateCodes(string $message): string
    {
        return preg_replace_callback('/(?<!:)\b\d{4,8}\b/', function(array $match): string {
            return substr($match[0], 0, 1).str_repeat('*', strlen($match[0]) - 1);
        }, $message) ?? $message;
    }

}

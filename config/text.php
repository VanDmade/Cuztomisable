<?php
// Text message delivery/logging settings.
return [
    // Determines if sent text messages are logged to text_logs.
    'log' => true,
    // Truncates any verification links in a logged message down to just the host, so a log
    // viewer can't use it to complete verification/reset/MFA as the user.
    'redact_urls' => true,
    // Truncates any standalone 4-8 digit code (MFA codes, etc.) in a logged message down to its
    // first digit.
    'redact_codes' => true,
    // When enabled, redact the entire logged message content instead of just urls/codes above.
    'redact_message' => false,
    // Optional regex patterns to redact in logs; when empty, full message is replaced.
    'redact_patterns' => [],
    // Replacement text used for redaction.
    'redact_replacement' => '********',
];

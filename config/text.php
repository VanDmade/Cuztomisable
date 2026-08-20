<?php

return [
    // Determines if sent text messages are logged to text_logs.
    'log' => true,
    // When enabled, redact the logged message content.
    'redact_message' => false,
    // Optional regex patterns to redact in logs; when empty, full message is replaced.
    'redact_patterns' => [],
    // Replacement text used for redaction.
    'redact_replacement' => '********',
];

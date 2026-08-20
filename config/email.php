<?php

return [
    // Determines if sent emails are logged to email_logs.
    'log' => true,
    // List of all parameters hidden within the email log.
    'hidden_parameters' => [
        'password',
    ],
    // Default logo path used in email templates.
    'logo' => 'images/logo.png',
    // Optional default from address for package emails.
    'from' => [
        'address' => null,
        'name'    => null,
    ],
    // Optional default reply-to address for package emails.
    'reply_to' => [
        'address' => null,
        'name'    => null,
    ],
];

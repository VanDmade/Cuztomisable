<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Settings
    |--------------------------------------------------------------------------
    */
    'emails' => [
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
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS / Text Settings
    |--------------------------------------------------------------------------
    */
    'texts' => [
        // When enabled, redact the logged message content.
        'redact_message'     => false,
        // Optional regex patterns to redact in logs; when empty, full message is replaced.
        'redact_patterns'    => [],
        // Replacement text used for redaction.
        'redact_replacement' => '********',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Templates
    |--------------------------------------------------------------------------
    | type: email | phone | email|phone
    | view: the view/template path used to render the notification
    */

    // Sent when a user logs in from an unrecognised IP address
    'new_ip_address' => [
        'type'                => 'email',
        'view'                => 'emails.authentication.new_ip_address',
        // Skip notification for recognised device fingerprints
        'skip_known_devices'  => false,
        // CIDR ranges that skip new IP notifications
        'trusted_ip_ranges'   => [],
    ],

    // Multi-factor authentication code (requires login.multi_factor_authentication.allowed = true)
    'mfa' => [
        'type' => 'email|phone',
        'view' => 'emails.authentication.mfa',
    ],

    // User invitation
    'invitation' => [
        'type' => 'email',
        'view' => 'emails.authentication.invitation',
    ],

    // Password reset link
    'reset' => [
        'type' => 'email',
        'view' => 'emails.authentication.reset',
    ],

    // Forgot password request
    'forgot' => [
        'type' => 'email',
        'view' => 'emails.authentication.forgot',
    ],

    // Email address verification
    'email_verification' => [
        'type' => 'email',
        'view' => 'emails.authentication.verifications.email',
    ],

    // Phone number verification
    'phone_verification' => [
        'type'  => 'phone',
        'view'  => 'emails.authentication.verifications.phone',
    ],

    // Registration confirmation sent to the new user
    'registration' => [
        'type' => 'email|phone',
        'view' => 'emails.authentication.registration',
    ],

    // Notification sent to the admin/inviter when a new user registers
    'registered' => [
        'type' => 'email',
        'view' => 'emails.authentication.registered',
    ],

    // Support message
    'support' => [
        'type' => 'email',
        'view' => 'emails.support',
    ],

    // Password changed confirmation
    'changed' => [
        'type' => 'email',
        'view' => 'emails.passwords.changed',
    ],

    // Temporary password sent by an administrator
    'temporary' => [
        'type' => 'email',
        'view' => 'emails.passwords.temporary',
    ],

];

<?php

return [
    'passwords' => [
        'reset_with' => [
            'email' => true,
            'phone' => false,
        ],
        // Length of time between each allowed reset attempt in seconds
        'time_between_allowed_resets' => 300,
        // Specifies the length of time between allowed resends
        'resend_after' => 15,
        // The code is going to be recreated when the code is resent
        'recreate_code_on_resend' => false,
        /* Specifies that the reset code can be sent via phone and/or email.
         * If both set to false, the user's email will be used. */
        'send_via' => [
            'phone' => true,
            'email' => true,
        ],
        // The amount of passwords that need to be iterated through before using the same password again
        'reuse_after' => 3,
        // The requirements for a password
        'requirements' => [
            // Minimum allowed characters
            'min' => 8,
            // Maximum allowed characters, if null, then there is no maximum
            'max' => null,
            // Total special characters (!@#!#$@) that have to be used
            'special_characters' => 1,
            // Total amount of numbers that have to be used
            'numbers' => 1,
            // Total amount of uppercase characters that have to be used
            'uppercase_characters' => 1,
        ],
    ],
    'code' => [
        // Length of all codes sent to users
        'length' => 6,
        // Length of time the code will expire
        'expires_in' => 300,
    ],
    'registration' => [
        // Determines if registration is disabled
        'disabled' => false,
        // Determines if registration can occur with a code
        'code' => false,
        // Length of all codes sent to users
        'length' => 6,
        // Length of time the registration code will expire
        'expires_in' => 300,
        /* Determines whether an email should be sent to an administrator.
         * If the account was created by another user, that user will receive the notification instead.
         * Ensure that CUZTOMISABLE_ADMIN is set in the .env file for fallback notifications. */
        'send_notification' => true,
        /* Sets up the fields to be used within the registration form. Keep in mind that this is purely for the main
         * Cuztomisable view and if you need more fields you should extend the Registration controller and create your
         * own view. If set to false it will not show within the default registration form. You can ignore these fields
         * if you are creating your own frontend. */
        'address' => [
            // If required is set to false, then the address field will not be required to submit but will be displayed
            'required' => false,
            // Determines whether or not the Address Two line should be displayed
            'address_two' => true,
            // Determines whether or not the Address Three line should be displayed
            'address_three' => false,
        ],
    ],
    // Length of the token used within the URL, max length is 64
    'token_length' => 16,
    'notifications' => [
        /* Sets up the notifications within the system and how they are sent
         * type  email|phone, this value can be set to email, phone, or email|phone.
                  If email|phone, then the user shall have the ability to choose
                  whether the email/text is used to send the notfication
         * view  Content for the email|text */
        'reset' => [
            'type' => 'email',
            'view' => 'emails.authentication.reset',
        ],
        'forgot' => [
            'type' => 'email',
            'view' => 'emails.authentication.forgot',
        ],
        'email_verification' => [
            'type' => 'email',
            'view' => 'emails.authentication.verifications.email',
        ],
        'phone_verification' => [
            'type' => 'phone',
            'view' => 'emails.authentication.verifications.phone',
        ],
        'registration' => [
            'type' => 'email|phone',
            'view' => 'emails.authentication.registration',
        ],
        'registered' => [
            'type' => 'email',
            'view' => 'emails.authentication.registered',
        ],
        'support' => [
            'type' => 'email',
            'view' => 'emails.support',
        ],
    ],
];
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
        // Length of all codes sent to users
        'length' => 6,
        // Length of time the registration code will expire
        'expires_in' => 300,
        // List of fields that are required when signing up
        'fields' => [
            /*  You can add to this list if you'd like, with any custom field names. To prevent any issues with SQL
                injection, these fields will be used to check what comes in to prevent any updates of, for example,
                admin or roles. The template for each of these fields within the users table are as followed, If you set
                the field to true, i.e. 'name' => true, then it will be displayed and required. You can also reorganize
                where the fields are within the dropdown other than the password which is the last entry no matter what.
                KEEP IN MIND, this is just for the templates with this package

                'database_field' => [
                    // Whether the system will require that the field have a value
                    'required' => boolean,
                    // The list is used within the select to be selected when filling out the form, if required is set to
                    // false, then there will be a blank option that the user can default to and choose.
                    'list' => [],
                ] */
            'name' => true,
            'first_name' => false,
            'middle_name' => false,
            'last_name' => false,
            'suffix' => false,
            'title' => false,
            'address' => [
                'required' => false,
                'type' => 'address',
            ],
            'gender' => false,
            'username' => false,
            'email' => true,
            'phone' => [
                // This is the way to set the value to not required, if it is just set the entire array index to true
                'required' => false,
                'type' => 'phone',
            ],
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
            'view' => 'cuztomisable.emails.authentication.reset',
        ],
        'forgot' => [
            'type' => 'email',
            'view' => 'cuztomisable.emails.authentication.forgot',
        ],
        'email_verification' => [
            'type' => 'email',
            'view' => 'cuztomisable.emails.authentication.verifications.email',
        ],
        'phone_verification' => [
            'type' => 'phone',
            'view' => 'cuztomisable.emails.authentication.verifications.phone',
        ],
        'registration' => [
            'type' => 'email|phone',
            'view' => 'cuztomisable.emails.authentication.registration',
        ],
    ],
];
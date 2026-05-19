<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passwords
    |--------------------------------------------------------------------------
    */
    'passwords' => [
        'reset_with' => [
            'email' => true,
            'phone' => false,
        ],
        // Seconds between each allowed password reset attempt
        'time_between_allowed_resets' => 300,
        // Seconds between allowed resends of the reset code
        'resend_after'                => 15,
        // Regenerate the code each time it is resent
        'recreate_code_on_resend'     => false,
        // Channels the reset code can be delivered through
        'send_via' => [
            'phone' => true,
            'email' => true,
        ],
        // Number of previous passwords that must differ before reuse is allowed
        'reuse_after'              => 3,
        // Minimum seconds required between password changes (0 = no cooldown)
        'change_cooldown_seconds'  => 0,
        // Password complexity requirements
        'requirements' => [
            'min'                  => 8,
            'max'                  => null,
            'special_characters'   => 1,
            'numbers'              => 1,
            'uppercase_characters' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification Codes
    |--------------------------------------------------------------------------
    */
    'code' => [
        // Number of digits/characters in generated codes
        'length'     => 6,
        // Seconds before a code expires
        'expires_in' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Locking
    |--------------------------------------------------------------------------
    | When true, new accounts are locked by default and must be unlocked
    | by an administrator before the user can log in.
    */
    'locked_by_default' => true,

    /*
    |--------------------------------------------------------------------------
    | Registration
    |--------------------------------------------------------------------------
    */
    'registration' => [
        // Disable open registration (invite-only when true)
        'disabled'           => false,
        // Length of the invitation/registration code
        'length'             => 6,
        // Seconds before the registration code expires
        'expires_in'         => 3600,
        // Maximum verification attempts before the code is invalidated
        'attempts' => [
            'max' => 5,
        ],
        // Send a notification to the administrator when a new user registers
        'send_notification'  => true,
        // Seconds before the user can request another registration code
        'resend_after'       => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Address
    |--------------------------------------------------------------------------
    | Controls address fields in the user registration/profile form.
    | Set to false to hide address fields entirely.
    */
    'address' => [
        'required'      => false,
        'address_two'   => true,
        'address_three' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Administrator
    |--------------------------------------------------------------------------
    */
    'administrator' => [
        'temporary_password' => [
            // Seconds before a temporary password expires
            'expires_in'   => 300,
            // Seconds before an admin can issue another temporary password
            'resend_after' => 300,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Length
    |--------------------------------------------------------------------------
    | Length of URL tokens (max 64).
    */
    'token_length' => 16,

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Login Methods
    |--------------------------------------------------------------------------
    | Specifies how users can identify themselves on the login form.
    | If both email and phone are false, a username field will be used instead.
    */
    'login_with' => [
        'email' => true,
        'phone' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Remember Me
    |--------------------------------------------------------------------------
    | Allows the user to stay logged in across sessions.
    | When true, session_length is ignored for that session.
    */
    'remember' => false,

    /*
    |--------------------------------------------------------------------------
    | Auth Cookie
    |--------------------------------------------------------------------------
    | Name of the cookie used to store the web auth token.
    */
    'cookie_name' => 'api_token',

    /*
    |--------------------------------------------------------------------------
    | Session Length
    |--------------------------------------------------------------------------
    | Duration in seconds before the session expires. Set to null for no limit.
    */
    'session_length' => 900,

    /*
    |--------------------------------------------------------------------------
    | Login Attempts
    |--------------------------------------------------------------------------
    */
    'attempts' => [
        // Number of failed attempts before the account is restricted
        'total'  => 5,
        // If true, the account is permanently locked until manually unlocked
        'locked' => false,
        // Seconds the account is restricted before login is allowed again (ignored if locked = true)
        'timer'  => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification
    |--------------------------------------------------------------------------
    | Require email/phone verification before allowing login.
    */
    'verification' => [
        'email' => true,
        'phone' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Factor Authentication
    |--------------------------------------------------------------------------
    */
    'multi_factor_authentication' => [
        // Determines if the system allows users to set up and use MFA
        'allowed'                  => true,
        // Seconds before the user can resend the code
        'resend_after'             => 60,
        // Regenerate the code each time it is resent
        'recreate_code_on_resend'  => true,
        // Maximum allowed code attempts before invalidation
        'attempts' => [
            'max' => 5,
        ],
        // Channels the MFA code can be delivered through (email and/or phone)
        'send_via' => [
            'phone' => true,
            'email' => true,
        ],
    ],

];

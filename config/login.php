<?php

return [
    /* Specifies whether or not the user can log in with the following options.
     * If both email|phone are set to false, then a username will be used. If
     * username is not in the database table then email address will be used instead. */
    'login_with' => [
        'email' => true,
        'phone' => true,
    ],
    // Allows the user to remember the account, this will ignore the session_length if set to true
    'remember' => false,
    // Cookie name used for web auth token
    'cookie_name' => 'api_token',
    'multi_factor_authentication' => [
        // Determines if the system allows the user to setup and use Multi Factor Authentication
        'allowed' => true,
        // How fast the user can resend the email/text in seconds (After the first resend)
        'resend_after' => 300,
        // The code will be recreated if resent and old codes cannot be used
        'recreate_code_on_resend' => true,
        // Maximum allowed code attempts before invalidation
        'attempts' => [
            'max' => 5,
        ],
        /* Specifies that the MFA can be sent via phone and/or email.
         * If both set to false, the user's email will be used. */
        'send_via' => [
            'phone' => true,
            'email' => true,
        ],
    ],
    /* The amount of time, in seconds, that the user's session is set to. If null,
     * then there is no session length, the user will remain logged in forever (Or until idle). */ 
    'session_length' => 900,
    'attempts' => [
        // Amount of attempts before the account is put on hold
        'total' => 5,
        // Whether the account will be locked and unable to be logged in until the user is unlocked
        'locked' => false,
        /* Amount of time the account will be disabled until allowing of logins again. If "locked" is set to
         * true, this will be ignored. */
        'timer' => 300,
    ],
    // Determines if verification is required to login
    'verification' => [
        'email' => true,
        'phone' => false,
    ],
    'notifications' => [
        /* Sets up the notifications within the system and how they are sent
         * type  email|phone, this value can be set to email, phone, or email|phone.
                  If email|phone, then the user shall have the ability to choose
                  whether the email/text is used to send the notfication
         * view  Content for the email|text */
        'new_ip_address' => [
            'type' => 'email',
            'view' => 'emails.authentication.new_ip_address',
            // Skip notifications for recognized device fingerprints
            'skip_known_devices' => false,
            // CIDR ranges that will skip new IP notifications
            'trusted_ip_ranges' => [],
        ],
        // login.mfa has to be set to true in order for this to be used
        'mfa' => [
            'type' => 'email|phone',
            'view' => 'emails.authentication.mfa',
        ],
        // login.mfa has to be set to true in order for this to be used
        'invitation' => [
            'type' => 'email',
            'view' => 'emails.authentication.invitation',
        ],
    ],
    /* Mobile app user agent validation
     * enabled: require user agent matching for X-App-Platform=mobile
     * apps: list of allowed app names with optional min_version
     * platforms: allowed platform labels in UA
     * Example UA: AppName/v1.0 (Android)
     */
    'mobile_agent' => [
        'enabled' => true,
        // Optional API key to validate mobile clients bypassing CSRF
        'api_key' => null,
        'api_key_header' => 'X-App-Key',
        'apps' => [
            ['name' => 'Mixing Maverik', 'min_version' => '1.0.0'],
            ['name' => 'Cuztomisable', 'min_version' => '1.0.0'],
        ],
        'platforms' => ['Android', 'iOS', 'Other'],
        // Log failed user agent checks for troubleshooting
        'log_invalid' => false,
    ],
];
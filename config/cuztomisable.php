<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS Provider
    |--------------------------------------------------------------------------
    | SmsProviderInterface implementation used to send text messages.
    */
    'sms_provider' => \VanDmade\Cuztomisable\Sms\AwsSnsSmsProvider::class,

    /*
    |--------------------------------------------------------------------------
    | App
    |--------------------------------------------------------------------------
    */
    'app' => [

        /*
        |----------------------------------------------------------------------
        | Home Route
        |----------------------------------------------------------------------
        | The route authenticated users land on after login or when they visit
        | a guest-only page (e.g. /login) while already authenticated.
        */
        'home' => env('APP_HOME', '/portal'),

        /*
        |----------------------------------------------------------------------
        | Mobile Agent Validation
        |----------------------------------------------------------------------
        | Controls user-agent checks for requests from mobile apps.
        | Example UA format: AppName/v1.0 (Android)
        */
        'mobile_agent' => [
            // Require user-agent matching for X-App-Platform: mobile requests
            'enabled'          => true,
            // Optional API key to validate mobile clients bypassing CSRF
            'api_key'          => null,
            'api_key_header'   => 'X-App-Key',
            // Allowed apps — name must match the UA string, min_version is optional
            'apps' => [
                ['name' => 'Mixing Maverick', 'min_version' => '1.0.0'],
                ['name' => 'Cuztomisable',    'min_version' => '1.0.0'],
            ],
            // Allowed platform labels in the UA string
            'platforms'        => ['Android', 'iOS', 'Other'],
            // Log requests that fail agent validation (useful for debugging)
            'log_invalid'      => false,
        ],

        /*
        |----------------------------------------------------------------------
        | Navigation
        |----------------------------------------------------------------------
        | Sidebar/navbar links shown to authenticated users. Each entry supports:
        |   route — Vue Router route name
        |   path  — Fallback href
        |   text  — Tooltip / label
        |   icon  — Material Icons ligature
        */
        'navigation' => [],

    ],

    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */
    'login' => [

        /*
        |----------------------------------------------------------------------
        | Login Methods
        |----------------------------------------------------------------------
        | Specifies how users can identify themselves on the login form.
        | If both email and phone are false, a username field will be used instead.
        */
        'login_with' => [
            'email' => true,
            'phone' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | Remember Me
        |----------------------------------------------------------------------
        | Allows the user to stay logged in across sessions.
        | When true, session_length is ignored for that session.
        */
        'remember' => false,

        /*
        |----------------------------------------------------------------------
        | Auth Cookie
        |----------------------------------------------------------------------
        | Name of the cookie used to store the web auth token.
        */
        'cookie_name' => 'api_token',

        /*
        |----------------------------------------------------------------------
        | Session Length
        |----------------------------------------------------------------------
        | Duration in seconds before the session expires. Set to null for no limit.
        */
        'session_length' => 900,

        /*
        |----------------------------------------------------------------------
        | Login Attempts
        |----------------------------------------------------------------------
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
        |----------------------------------------------------------------------
        | Verification
        |----------------------------------------------------------------------
        | Require email/phone verification before allowing login.
        */
        'verification' => [
            'email' => true,
            'phone' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | Multi-Factor Authentication
        |----------------------------------------------------------------------
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

    ],

    /*
    |--------------------------------------------------------------------------
    | Account
    |--------------------------------------------------------------------------
    */
    'account' => [

        /*
        |----------------------------------------------------------------------
        | Passwords
        |----------------------------------------------------------------------
        */
        'passwords' => [
            'reset_with' => [
                'email' => true,
                'phone' => false,
            ],
            // Seconds between each allowed password reset attempt
            'time_between_allowed_resets' => 300,
            // Seconds between allowed resends of the reset code
            'resend_after' => 15,
            // Regenerate the code each time it is resent
            'recreate_code_on_resend' => false,
            // Channels the reset code can be delivered through
            'send_via' => [
                'phone' => false,
                'email' => true,
            ],
            // Number of previous passwords that must differ before reuse is allowed
            'reuse_after' => 3,
            // Minimum seconds required between password changes (0 = no cooldown)
            'change_cooldown_seconds'  => 0,
            // Password complexity requirements
            'requirements' => [
                'min' => 8,
                'max' => null,
                'special_characters' => 1,
                'numbers' => 1,
                'uppercase_characters' => 1,
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Verification Codes
        |----------------------------------------------------------------------
        */
        'code' => [
            // Number of digits/characters in generated codes
            'length' => 6,
            // Seconds before a code expires
            'expires_in' => 300,
        ],

        /*
        |----------------------------------------------------------------------
        | Account Locking
        |----------------------------------------------------------------------
        | When true, new accounts are locked by default and must be unlocked
        | by an administrator before the user can log in.
        */
        'locked_by_default' => true,

        /*
        |----------------------------------------------------------------------
        | Default Timezone
        |----------------------------------------------------------------------
        | Used for a new user's timezone when none is given at registration/creation.
        */
        'default_timezone' => 'America/New_York',

        /*
        |----------------------------------------------------------------------
        | Registration
        |----------------------------------------------------------------------
        */
        'registration' => [
            // Disable open registration (invite-only when true), per platform.
            // Identified via the X-App-Platform request header ('mobile' or otherwise treated as 'web').
            'disabled' => [
                'web' => false,
                'mobile' => false,
            ],
            // Length of the invitation/registration code
            'length' => 6,
            // Seconds before the registration code expires
            'expires_in' => 3600,
            // Maximum verification attempts before the code is invalidated
            'attempts' => [
                'max' => 5,
            ],
            // Send a notification to the administrator when a new user registers
            'send_notification' => true,
            // Seconds before the user can request another registration code
            'resend_after' => 300,
        ],

        /*
        |----------------------------------------------------------------------
        | Address
        |----------------------------------------------------------------------
        | Controls address fields in the user registration/profile form.
        | Set to false to hide address fields entirely.
        */
        'address' => [
            'required' => false,
            'address_two' => true,
            'address_three' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | Administrator
        |----------------------------------------------------------------------
        */
        'administrator' => [
            'temporary_password' => [
                // Seconds before a temporary password expires
                'expires_in' => 300,
                // Seconds before an admin can issue another temporary password
                'resend_after' => 300,
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Token Length
        |----------------------------------------------------------------------
        | Length of URL tokens (max 64).
        */
        'token_length' => 16,

    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile
    |--------------------------------------------------------------------------
    | Was never actually merged under the old ten-file config setup (config/mobile.php
    | existed but CuztomisableServiceProvider never called mergeConfigFrom() on it) - every
    | cuztomisable.mobile.* read silently fell back to its hardcoded default. Fixed by this
    | consolidation: being a section of this one file means it's merged automatically.
    */
    'mobile' => [
        'refresh' => [
            // The token will be updated and returned anytime it is reset
            'reset_token' => false,
            // Total days the token will be usable without refresh.
            'expires_in' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [

        /*
        |----------------------------------------------------------------------
        | Notification Templates
        |----------------------------------------------------------------------
        | Email and text delivery settings (logging, redaction, from/reply-to,
        | etc.) live in config/email.php and config/text.php, merged in here as
        | notifications.emails / notifications.texts by the service provider.
        | The actual view each notification renders is hardcoded on its Mailable
        | (view: 'cuztomisable::...') - to customize a template, publish it with
        | `php artisan vendor:publish --tag=cuztomisable-views` and edit the copy
        | under resources/views/vendor/cuztomisable, rather than repointing a
        | config path. This section only holds delivery settings.
        */

        // Sent when a user logs in from an unrecognised IP address
        'new_ip_address' => [
            'enabled' => true,
            // Which channel(s) this alert goes out on - independent toggles (not either/or),
            // since a security alert may legitimately warrant both at once.
            'send_via' => [
                'email' => true,
                'phone' => false,
            ],
            // Skip notification for recognised device fingerprints
            'skip_known_devices'  => false,
            // CIDR ranges that skip new IP notifications
            'trusted_ip_ranges'   => [],
        ],

        // Email address verification
        'email_verification' => [
            'enabled' => true,
        ],

        // Phone number verification
        'phone_verification' => [
            'enabled' => true,
        ],

        // Confirmation sent to the user after their password is reset
        'reset' => [
            'enabled' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Locations
    |--------------------------------------------------------------------------
    */
    'locations' => [
        // Default country that will be selected when filling out the form, set to null if you want them to choose.
        'default_country' => 'USA',
        /* List of all allowed countries and their states/provinces. If the states/provinces are left blank then that
         * dropdown will be removed. If you only have one option, then they will not be able to select a country and it
         * will be defaulted */
        'countries' => [
            [
                'value' => 'USA',
                'text' => 'United States',
                // Whether the city input should exist
                'city' => true,
                // List of all states / provinces to be displayed when a country is picked
                'states_or_provinces' => [
                    ['value' => 'AL', 'text' => 'Alabama'],
                    ['value' => 'AK', 'text' => 'Alaska'],
                    ['value' => 'AZ', 'text' => 'Arizona'],
                    ['value' => 'AR', 'text' => 'Arkansas'],
                    ['value' => 'CA', 'text' => 'California'],
                    ['value' => 'CO', 'text' => 'Colorado'],
                    ['value' => 'CT', 'text' => 'Connecticut'],
                    ['value' => 'DE', 'text' => 'Delaware'],
                    ['value' => 'DC', 'text' => 'District Of Columbia'],
                    ['value' => 'FL', 'text' => 'Florida'],
                    ['value' => 'GA', 'text' => 'Georgia'],
                    ['value' => 'HI', 'text' => 'Hawaii'],
                    ['value' => 'ID', 'text' => 'Idaho'],
                    ['value' => 'IL', 'text' => 'Illinois'],
                    ['value' => 'IN', 'text' => 'Indiana'],
                    ['value' => 'IA', 'text' => 'Iowa'],
                    ['value' => 'KS', 'text' => 'Kansas'],
                    ['value' => 'KY', 'text' => 'Kentucky'],
                    ['value' => 'LA', 'text' => 'Louisiana'],
                    ['value' => 'ME', 'text' => 'Maine'],
                    ['value' => 'MD', 'text' => 'Maryland'],
                    ['value' => 'MA', 'text' => 'Massachusetts'],
                    ['value' => 'MI', 'text' => 'Michigan'],
                    ['value' => 'MN', 'text' => 'Minnesota'],
                    ['value' => 'MS', 'text' => 'Mississippi'],
                    ['value' => 'MO', 'text' => 'Missouri'],
                    ['value' => 'MT', 'text' => 'Montana'],
                    ['value' => 'NE', 'text' => 'Nebraska'],
                    ['value' => 'NV', 'text' => 'Nevada'],
                    ['value' => 'NH', 'text' => 'New Hampshire'],
                    ['value' => 'NJ', 'text' => 'New Jersey'],
                    ['value' => 'NM', 'text' => 'New Mexico'],
                    ['value' => 'NY', 'text' => 'New York'],
                    ['value' => 'NC', 'text' => 'North Carolina'],
                    ['value' => 'ND', 'text' => 'North Dakota'],
                    ['value' => 'OH', 'text' => 'Ohio'],
                    ['value' => 'OK', 'text' => 'Oklahoma'],
                    ['value' => 'OR', 'text' => 'Oregon'],
                    ['value' => 'PA', 'text' => 'Pennsylvania'],
                    ['value' => 'RI', 'text' => 'Rhode Island'],
                    ['value' => 'SC', 'text' => 'South Carolina'],
                    ['value' => 'SD', 'text' => 'South Dakota'],
                    ['value' => 'TN', 'text' => 'Tennessee'],
                    ['value' => 'TX', 'text' => 'Texas'],
                    ['value' => 'UT', 'text' => 'Utah'],
                    ['value' => 'VT', 'text' => 'Vermont'],
                    ['value' => 'VA', 'text' => 'Virginia'],
                    ['value' => 'WA', 'text' => 'Washington'],
                    ['value' => 'WV', 'text' => 'West Virginia'],
                    ['value' => 'WI', 'text' => 'Wisconsin'],
                    ['value' => 'WY', 'text' => 'Wyoming'],

                ],
            ]
        ],
        'default_country_code' => 1,
        // List of allowed country codes and the country they belong to. The format will be used to replace X with digits
        'country_codes' => [
            ['text' => '(+1) USA', 'value' => 1, 'format' => '(XXX) XXX-XXXX', 'required_length' => 10],
            ['text' => '(+44) UK', 'value' => 44, 'format' => '(XX) XXXX-XXXX', 'required_length' => 10],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Images
    |--------------------------------------------------------------------------
    */
    'images' => [

        /*
        |----------------------------------------------------------------------
        | Default Width
        |----------------------------------------------------------------------
        | Uploaded images are scaled down to this width (in pixels) before storage.
        */
        'default_width' => 1200,

        /*
        |----------------------------------------------------------------------
        | Default Quality
        |----------------------------------------------------------------------
        | Starting WebP encoding quality (0-100). Lowered in steps until the
        | encoded image fits within `default_size`.
        */
        'default_quality' => 80,

        /*
        |----------------------------------------------------------------------
        | Default Size
        |----------------------------------------------------------------------
        | Maximum encoded file size in bytes. Quality is reduced until the
        | image fits, or the minimum quality floor is reached.
        */
        'default_size' => 300 * 1024,

    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'default' => [
            'attempts' => 5,
            'decay_seconds' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Respondify (response formatting - see Support\ResponseService, Step 5)
    |--------------------------------------------------------------------------
    */
    'respondify' => [
        // Debug code that will be returned when using the debug method
        'debugging_response_code' => 500,
        // Size of the debug code within the error
        'debug_code_size' => 6,
        'errors' => [
            // Determines if the errors should be logged
            'log' => false,
            // Default response code when an error occurs
            'code' => 500,
            // The model used for logging
            'model' => null,
            // Columns used to store the error information, if any are set to null, they will be placed within the parameters
            'columns' => [
                'message' => 'message',
                'line' => 'line',
                'file' => 'file',
                'code' => 'code',
                // If this is set to null, no additional information will be stored
                'parameters' => 'parameters',
            ],
            // Code that will output if there is a SQL error
            'sql_error_code' => 500,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tablelify (paginated table queries - see Support\TableService, Step 5)
    |--------------------------------------------------------------------------
    */
    'tablelify' => [
        // References whether the filtered total will need to be displayed along with the base query's total
        'filtered' => true,
        // Whether to return the next/previous page parameter in the table response
        'page_details' => true,
        // Max page size for any table request
        'max_size' => 100,
        'default' => [
            // Default page size, if for some reason the page size is not sent in
            'size' => 10,
            'order_by' => 'id',
            'order_direction' => 'desc',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Organizations (multi-tenancy - Step 9, still mostly not built)
    |--------------------------------------------------------------------------
    */
    'organizations' => [
        // The Organization model class - swap this if your app brings its own instead of
        // using the one Cuztomisable ships by default.
        'organization_model' => \VanDmade\Cuztomisable\Models\Organizations\Organization::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP / TOTP (Step 10, not built yet)
    |--------------------------------------------------------------------------
    */
    'otp' => [],

    /*
    |--------------------------------------------------------------------------
    | Social account linking (Step 11, not built yet)
    |--------------------------------------------------------------------------
    */
    'social' => [],

    /*
    |--------------------------------------------------------------------------
    | Terms & conditions / cookie consent (Step 12, not built yet)
    |--------------------------------------------------------------------------
    */
    'terms' => [],

];

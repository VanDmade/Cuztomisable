<?php
// Social login providers. "enabled" is a master on/off switch; each provider also has its own.
// All providers are off by default until turned on and given real credentials.
return [
    'enabled' => true,
    'providers' => [
        'google' => [
            'enabled' => false,
            'logo' => true,
            'label' => 'Continue with Google',
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_REDIRECT_URI'),
        ],
        'facebook' => [
            'enabled' => false,
            'logo' => true,
            'label' => 'Continue with Facebook',
            'client_id' => env('FACEBOOK_CLIENT_ID'),
            'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
            'redirect' => env('FACEBOOK_REDIRECT_URI'),
        ],
        'linkedin' => [
            'enabled' => false,
            'logo' => true,
            'label' => 'Continue with LinkedIn',
            'client_id' => env('LINKEDIN_CLIENT_ID'),
            'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
            'redirect' => env('LINKEDIN_REDIRECT_URI'),
        ],
        'twitter' => [
            'enabled' => false,
            'logo' => true,
            'label' => 'Continue with X',
            'client_id' => env('TWITTER_CLIENT_ID'),
            'client_secret' => env('TWITTER_CLIENT_SECRET'),
            'redirect' => env('TWITTER_REDIRECT_URI'),
        ],
        'instagram' => [
            'enabled' => false,
            'logo' => true,
            'label' => 'Continue with Instagram',
            'client_id' => env('INSTAGRAM_CLIENT_ID'),
            'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),
            'redirect' => env('INSTAGRAM_REDIRECT_URI'),
        ],
    ],
];

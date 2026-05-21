<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Home Route
    |--------------------------------------------------------------------------
    | The route authenticated users land on after login or when they visit
    | a guest-only page (e.g. /login) while already authenticated.
    */
    'home' => env('APP_HOME', '/portal'),

    /*
    |--------------------------------------------------------------------------
    | Mobile Agent Validation
    |--------------------------------------------------------------------------
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
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    | Sidebar/navbar links shown to authenticated users. Each entry supports:
    |   route — Vue Router route name
    |   path  — Fallback href
    |   text  — Tooltip / label
    |   icon  — Material Icons ligature
    */
    'navigation' => [],

];

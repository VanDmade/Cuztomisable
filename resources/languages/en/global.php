<?php

return [
    'unauthorized' => 'It looks like you\'ve wandered into a part of the site you don\'t have access to. Maybe it\'s top secret, maybe it\'s just above your pay grade — either way, no peeking!',
    'unauthenticated' => 'You need to be logged in to do that.',
    'invalid_user_agent' => 'Your browser is being a bit shy. Please try again with a more cooperative one.',
    'note_to_nosey' => 'All of these fields are validated in the server, these fields are just for ease of use within the GUI...',
    'server_broken' => 'Hang tight — we hit a snag. Try again shortly, and we\'ll be back on track.',
    'form' => [
        'required' => 'This field is required.',
        'email' => 'This field must be an email address.',
        'exists' => 'The value entered doesn\'t match our requirements.',
        'in' => 'Please refresh and try again!',
        'boolean' => 'The value must be true or false.',
        'unique' => 'This field must be unique.',
        'phone' => [
            'size' => 'Please enter a valid phone number.',
        ],
    ],
    'images' => [
        'default_width' => 1200,
        'default_quality' => 80,
        'default_size' => 300 * 1024, // 300KB
    ],
];
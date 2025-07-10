<?php

return [
    'emails' => [
        'subjects' => [
            'mfa' => 'Multi-Factor Authentication',
            'forgot' => 'Forgot Password',
            'reset' => 'Reset Password',
            'new_ip_address' => 'New Login Detected',
            'email_verification' => 'Email Verification',
            'registered' => 'New User',
            'invitation' => 'Invitation',
        ],
    ],
    'login' => [
        'logged_in' => 'You have logged in.',
        'mfa_logged_in' => 'Please verify this login attempt.',
        'logged_out' => 'You have logged out.',
        'errors' => [
            'locked' => 'This account is currently locked.',
            'invalid_credentials' => 'The credentials are invalid.',
            'attempts' => 'Your account is currently disabled due to too many attempts. Please come back later.',
            'verification' => [
                'email_required' => 'Please verify your email address before continuing.',
                'phone_required' => 'Please verify your phone number before continuing.',
            ],
        ],
    ],
    'mfa' => [
        'sent' => 'The code was :sent to :location.',
        'verified' => 'The request has been verified.',
        'phone_message' => '[:company] Your verification code is: :code',
        'errors' => [
            'not_created' => 'There was an error while creating your MFA code.',
            'not_found' => 'The code was not found.',
            'token_has_expired' => 'This request is no longer valid.',
            'sent_recently' => 'The code was recently resent. Try again later and check your spam/junk folder.',
            'expired' => 'The code has expired.',
            'ip_address_not_found' => 'Something has occurred and the IP address doesn\'t match.',
        ],
    ],
    'passwords' => [
        'sent' => 'The password reset code was sent.',
        'resent' => 'The password reset code was resent.',
        'reset' => 'The password was reset.',
        'locked' => 'The account is currently locked, so please reach out to an administrator in order to login. You '.
            'are still able to reset your password, but will not be able to login.',
        'verified' => 'The reset token has been verified.',
        'code_verified' => 'The reset code has been verified.',
        'errors' => [
            'not_found' => 'The code was not found.',
            'expired' => 'The reset code has expired. Please try again!',
            'invalid_code' => 'The code entered is invalid. Please try again!',
            'already_sent' => 'The code was already sent. Please try again later or click the link in the email.',
            'sent_recently' => 'The code was recently resent. Try again later and check your spam/junk folder.',
            'used_recently' => 'The password was used recently. Please use a new password.',
            'attempt_counter' => 'You\'ve entered too many codes. Please restart the reset process.',
        ],
    ],
    'registration' => [
        'verified' => 'The code has been verified.',
        'created' => 'Welcome to the application!',
        'verification' => 'Welcome to the application! Please verify your :type before you login.',
        'invite' => 'You\'ve successfully invited the new user.',
        'deleted' => 'The registration link has been deleted.',
        'undo' => 'The registration link was reactivated.',
        'resent' => 'The registration was resent.',
        'disabled' => 'Registration is currently disabled, which means new users aren\'t able to create accounts on their own at this time. If you\'re interested in becoming part of the platform or believe you\'ve reached this message in error, we encourage you to contact an administrator or a member of our support team. We\'d love to learn more about your interest, explore the opportunity for you to join, and provide any guidance or next steps you might need. Your enthusiasm is genuinely appreciated, and we\'re here to help however we can. Feel free to reach out to us at :email — we look forward to hearing from you!',
        'sms' => [
            'invited' => '[:company] Hey there! You\'ve got something special — visit our site and sign up using the following link: :url',
            'verification' => '[:company] Don’t leave us hanging — tap the link and verify your number: :url',
        ],
        'errors' => [
            'missing' => 'This registration link has expired, been used, or no longer exists.',
            'sent_recently' => 'A registration link was sent recently. Please wait before sending another.',
            'recently_invited' => 'This user was recently invited. If they need a new code, locate them in the list and resend the invitation link.',
            'not_found' => 'The code entered was not found.',
            'used' => 'The registration code was already used.',
            'expired' => 'The registration code has already expired. Please contact an administrator.',
            'deleted' => 'The registration code is no longer able to be used. Please contact an administrator.',
            'values_missing' => 'Either an email address or phone number is required. Please provide at least one.',
            'already_used' => 'This email address or phone number is already associated with another account. Please check if the user already exists in the system.',
        ],
    ],
];
<?php

return [
    'emails' => [
        'subjects' => [
            'support' => 'Support Message',
            'changed' => 'Changed Password',
            'temporary' => 'Your Temporary Password Has Been Issued',
        ],
    ],
    'saved' => 'You have saved this user.',
    'deleted' => 'The user was deleted.',
    'undo' => 'The user was added backed into the system.',
    'locked' => 'This account has been locked.',
    'unlocked' => 'This account has been unlocked.',
    'account' => [
        'could_not_lock' => 'The account could not be locked. If you still want to lock the account, please reach out to an administrator',
        'self_locked' => 'You have locked your account and an email was sent to our administrators. We will reach out shortly to help you resolve this issue.',
        'already_locked' => 'Your account is already locked.',
        'temporary_password' => 'You have sent a temporary password to this user.',
    ],
    'ip_address' => [
        'deleted' => 'The IP address was deleted.',
        'undo' => 'The IP address was added backed into the system.',
        'errors' => [
            'not_found' => 'The IP Address was not found.',
            'not_remembered' => 'There\'s no need to forget this IP address, as it was never marked to be remembered.',
        ],
    ],
    'mfa' => [
        'enabled' => 'You have enabled multi-factor authentication.',
        'disabled' => 'You have disabled multi-factor authentication.',
    ],
    'errors' => [
        'not_found' => 'The user was not found.',
        'locked' => 'Your account is currently locked.',
        'delete_my_account' => 'You cannot delete your own account.',
        'incorrect_password' => 'Hmm... You are unable to change your password.',
        'password_changed_recently' => 'An administrator recently reset this user\'s password. Please try again later and ask the user to check their spam or junk folder.',
    ],
    'image' => [
        'errors' => [
            'not_found' => 'The image was not found.',
        ],
    ],
];
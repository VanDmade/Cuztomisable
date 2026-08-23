<?php

return [
    'access' => [
        'saved' => 'You have saved this user\'s access.',
    ],
    'saved' => 'You have saved this user.',
    'created' => 'The user was created and a temporary password was emailed to them.',
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
    'refresh' => [
        'refreshed' => 'Token refreshed.',
        'errors' => [
            'not_found' => 'Your session has expired. Please log in again.',
            'user_not_found' => 'We could not verify your account. Please log in again.',
            'revoked' => 'This session has been revoked and can no longer be used. Please log in again.',
        ],
    ],
    'phone' => [
        'saved' => 'The phone number was saved.',
        'created' => 'The phone number was added.',
        'default' => 'This is now your default phone number.',
        'deleted' => 'The phone number was deleted.',
        'undo' => 'The phone number was added backed into the system.',
        'errors' => [
            'not_found' => 'The phone number was not found.',
        ],
    ],
    'address' => [
        'saved' => 'The address was saved.',
        'created' => 'The address was added.',
        'default' => 'This is now your default address.',
        'deleted' => 'The address was deleted.',
        'undo' => 'The address was added backed into the system.',
        'errors' => [
            'not_found' => 'The address was not found.',
        ],
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
    'password' => [
        'changed' => 'You have changed your password!',
    ],
    'verification' => 'Success! Your :type has been successfully verified. You may now continue using your account without interruption. If you were in the middle of a process, you can safely return to it. We appreciate your attention to keeping your account secure.',
    'unsubscribe' => 'You have been successfully unsubscribed. You will no longer receive notifications to your :type.',
    'errors' => [
        'not_found' => 'The user was not found.',
        'locked' => 'Your account is currently locked.',
        'delete_my_account' => 'You cannot delete your own account.',
        'incorrect_password' => 'Hmm... You are unable to change your password.',
        'password_changed_recently' => 'An administrator recently reset this user\'s password. Please try again later and ask the user to check their spam or junk folder.',
        'invalid_verification' => 'An error occurred while trying to verify your :type. Please try again, or request a new verification link if the issue continues. We\'re here to help if you need assistance.',
        'invalid_unsubscribe' => 'We couldn\'t process your unsubscribe request. The link may be invalid, expired, or already used.',
        'email_in_use' => 'This email address is already in use by another account. If this user already has an account, they can reset their password instead.',
        'no_force_change_allowed' => 'Hmm… You can’t change your password this way. It’s possible there was an error or the password has already been updated. Please refresh the page and try again.',
    ],
    'image' => [
        'errors' => [
            'not_found' => 'The image was not found.',
        ],
    ],
];
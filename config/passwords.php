<?php

/*
|--------------------------------------------------------------------------
| Passwords
|--------------------------------------------------------------------------
| Forgot/reset flow, plus reuse/change settings shared with the authenticated change-password
| flow. Merged into cuztomisable.account.passwords at boot - this file exists purely to keep the
| source organized; the app still only ever publishes and edits the single config/cuztomisable.php.
*/

return [
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
    'change_cooldown_seconds' => 0,
    // Password complexity requirements
    'requirements' => [
        'min' => 8,
        'max' => null,
        'special_characters' => 1,
        'numbers' => 1,
        'uppercase_characters' => 1,
    ],
    // Whether visiting a reset email's "lock my account" link is allowed to actually lock it
    'allow_lock_via_token' => false,
    // How long after a reset request the lock-via-token link stays valid, in seconds
    'lock_window_seconds' => 86400,
];

<?php
// Rate limits per throttled action, e.g. 'ip_addresses' => ['toggle_delete' => ['attempts' =>
// 10, 'decay_seconds' => 120]]. Falls back to "default" when an action has no override.
return [
    'default' => [
        'attempts' => 5,
        'decay_seconds' => 60,
    ],
];

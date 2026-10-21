<?php
// Rate limits per throttled action, e.g. 'ip_addresses' => ['toggle_delete' => ['attempts' =>
// 10, 'decay_seconds' => 120]]. Falls back to "default" when an action has no override.
return [
    'default' => [
        'attempts' => 5,
        'decay_seconds' => 60,
    ],
    // Choices are searched as the user types, and answers can be saved one question at a time
    'questionnaire' => [
        'options' => ['attempts' => 60, 'decay_seconds' => 60],
        'save' => ['attempts' => 30, 'decay_seconds' => 60],
    ],
];

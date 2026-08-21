<?php

/*
|--------------------------------------------------------------------------
| Rate Limits
|--------------------------------------------------------------------------
| Merged into cuztomisable.rate_limits at boot - this file exists purely to
| keep the source organized; the app still only ever publishes and edits
| the single config/cuztomisable.php.
|
| Throttler (Middleware\Throttler) looks up "cuztomisable.rate_limits.<action>"
| for a per-action override, falling back to "default" - since the action
| name is itself dot-separated (e.g. "ip_addresses.toggle_delete"), an
| override for it is just a nested array here:
|
|   'ip_addresses' => [
|       'toggle_delete' => ['attempts' => 10, 'decay_seconds' => 120],
|   ],
*/

return [
    'default' => [
        'attempts' => 5,
        'decay_seconds' => 60,
    ],
];

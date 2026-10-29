<?php

namespace VanDmade\Cuztomisable\Concerns;

/**
 * Normalizes null attribute values to empty strings on save.
 */
trait NullsToEmpty
{

    protected function nullsToEmpty(array $data): array
    {
        array_walk_recursive($data, function (&$value): void {
            // FormData can't send a real null, so frontends send these words instead
            if ($value === null || $value === 'null' || $value === 'undefined') {
                $value = '';
            }
        });
        return $data;
    }

}

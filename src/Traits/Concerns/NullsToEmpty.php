<?php

namespace VanDmade\Cuztomisable\Traits\Concerns;

trait NullsToEmpty
{

    protected function nullsToEmpty(array $data): array
    {
        array_walk_recursive($data, function (&$value): void {
            if ($value === null || $value === 'null') {
                $value = '';
            }
        });
        return $data;
    }

}

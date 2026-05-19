<?php

namespace VanDmade\Cuztomisable\Traits\Concerns;

use Illuminate\Support\Facades\Auth;

trait Auditable
{

    public static function bootAuditable(): void
    {
        static::creating(function ($model): void {
            if (in_array('created_by', $model->getFillable())) {
                $model->created_by ??= Auth::id();
            }
        });
    }

}

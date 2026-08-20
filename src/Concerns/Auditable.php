<?php

namespace VanDmade\Cuztomisable\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait Auditable
{

    public static function bootAuditable(): void
    {
        static::creating(function($model) {
            // Makes sure the created_by column exists and is null before setting it
            if (Schema::hasColumn($model->getTable(), $model->getCreatedByColumn()) &&
                is_null($model->{$model->getCreatedByColumn()})) {
                $model->{$model->getCreatedByColumn()} = Auth::check() ? Auth::id() : null;
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model'),
            $this->getCreatedByColumn()
        );
    }

    public function getCreatedByColumn(): string
    {
        return defined(static::class.'::CREATED_BY') ?
            constant(static::class.'::CREATED_BY') : 'created_by';
    }

    protected function usesCreatedByColumn(): bool
    {
        return Schema::hasColumn($this->getTable(), $this->getCreatedByColumn());
    }

}

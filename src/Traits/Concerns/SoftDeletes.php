<?php

namespace VanDmade\Cuztomisable\Traits\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes as SoftDeletion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait SoftDeletes
{

    use SoftDeletion;

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), $this->getDeletedByColumn());
    }

    public function softDelete(): bool
    {
        return (bool) $this->delete();
    }

    protected function runSoftDelete(): void
    {
        $time = $this->freshTimestamp();
        $deletedAtColumn = $this->getDeletedAtColumn();
        $columns = [$deletedAtColumn => $this->fromDateTime($time)];
        if ($this->usesDeletedByColumn()) {
            $deletedBy = Auth::id();
            $columns[$this->getDeletedByColumn()] = $deletedBy;
            $this->{$this->getDeletedByColumn()} = $deletedBy;
        }
        $this->{$deletedAtColumn} = $time;
        $this->setKeysForSaveQuery($this->newModelQuery())->update($columns);
    }

    public function getDeletedByColumn(): string
    {
        return defined(static::class.'::DELETED_BY') ? constant(static::class.'::DELETED_BY') : 'deleted_by';
    }

    protected function usesDeletedByColumn(): bool
    {
        return Schema::hasColumn($this->getTable(), $this->getDeletedByColumn());
    }

}

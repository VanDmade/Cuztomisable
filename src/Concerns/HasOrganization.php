<?php

namespace VanDmade\Cuztomisable\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use VanDmade\Cuztomisable\Models\Organizations\Organization;

trait HasOrganization
{

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            config('cuztomisable.organizations.organization_model', Organization::class),
            $this->getOrganizationColumn()
        );
    }

    public function getOrganizationColumn(): string
    {
        return defined(static::class.'::ORGANIZATION') ?
            constant(static::class.'::ORGANIZATION') : 'organization_id';
    }

    protected function usesOrganizationColumn(): bool
    {
        return Schema::hasColumn($this->getTable(), $this->getOrganizationColumn());
    }

}

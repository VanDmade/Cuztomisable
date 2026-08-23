<?php

namespace VanDmade\Cuztomisable\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use VanDmade\Cuztomisable\Models\Organizations\Organization;
use VanDmade\Cuztomisable\Scopes\OrganizationScope;

/**
 * For a resource that belongs to exactly one organization (e.g. Roles\Role, Permission) - a
 * model using this trait is automatically scoped to the current user's current organization on
 * every query, including relationship queries, via OrganizationScope. See that class's docblock
 * for the fail-closed behavior this depends on.
 */
trait HasOrganization
{

    public static function bootHasOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope());
    }

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

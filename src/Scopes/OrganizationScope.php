<?php

namespace VanDmade\Cuztomisable\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Scopes a model's queries to the current user's organization.
 */
class OrganizationScope implements Scope
{

    public function apply(Builder $builder, Model $model): void
    {
        if (!config('cuztomisable.organizations.enabled', false)) {
            // The whole feature is off for this app - nothing to scope
            return;
        }
        if (!Auth::check()) {
            // No authenticated user to scope by at all - console commands, seeders, queued jobs,
            // etc. There's no per-request tenant context here, so these are inherently
            // administrative and see everything, same as a platform admin would.
            return;
        }
        $user = Auth::user();
        if ($user?->admin) {
            // Platform admins aren't scoped to a single organization - they see across all tenants.
            return;
        }
        $organizationId = $user?->organization_id;
        if (is_null($organizationId)) {
            // Force false to prevent any data returned that shouldn't be
            $builder->whereRaw('1 = 0');
            return;
        }
        $column = $model->qualifyColumn($model->getOrganizationColumn());
        $builder->where(function($query) use ($column, $organizationId) {
            $query->whereNull($column)
                ->orWhere($column, '=', $organizationId);
        });
    }

}

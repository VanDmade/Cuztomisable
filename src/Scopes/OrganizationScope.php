<?php

namespace VanDmade\Cuztomisable\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class OrganizationScope implements Scope
{

    public function apply(Builder $builder, Model $model): void
    {
        if (!config('cuztomisable.organizations.enabled', false)) {
            // The whole feature is off for this app - nothing to scope
            return;
        }
        $organizationId = Auth::user()?->organization_id;
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

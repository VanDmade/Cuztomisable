<?php

namespace VanDmade\Cuztomisable\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use VanDmade\Cuztomisable\Models\Organizations\Organization;
use Exception;

class OrganizationService
{

    public function find($id): Organization
    {
        $organization = Organization::where('id', '=', $id)->first();
        if (!isset($organization->id)) {
            throw new Exception(__('cuztomisable/organizations.errors.not_found'), 404);
        }
        return $organization;
    }

    public function list(Model $user): Collection
    {
        return $user->organizations()->get();
    }

    public function switch(Model $user, $organizationId): Organization
    {
        $organization = $this->find($organizationId);
        $user->switchTo($organization);
        return $organization;
    }

}

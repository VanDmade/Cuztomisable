<?php

namespace VanDmade\Cuztomisable\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use VanDmade\Cuztomisable\Models\Organizations\Organization;
use VanDmade\Cuztomisable\Models\Organizations\User as OrganizationMembership;

/**
 * Gives the user model many-to-many organization membership (organization_user) plus a "current
 * organization" pointer - users.organization_id, a single column, not a `current` flag scattered
 * across membership rows. That's what OrganizationScope reads (via organization_id directly, no
 * join) to decide what a request is even allowed to see, so switchTo() must only ever leave it
 * pointing at an organization this user actually belongs to.
 */
trait BelongsToOrganizations
{

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(
            config('cuztomisable.organizations.organization_model', Organization::class),
            'organization_user'
        )->using(OrganizationMembership::class)->withTimestamps();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            config('cuztomisable.organizations.organization_model', Organization::class),
            'organization_id'
        );
    }

    public function currentOrganization(): ?Model
    {
        return $this->organization()->first();
    }

    public function switchTo(Model $organization): bool
    {
        return DB::transaction(function() use ($organization) {
            $isMember = OrganizationMembership::where('user_id', '=', $this->id)
                ->where('organization_id', '=', $organization->id)
                ->exists();
            if (!$isMember) {
                throw new Exception(__('cuztomisable/organizations.errors.not_a_member'), 403);
            }
            $this->organization_id = $organization->id;
            $this->save();
            return true;
        });
    }

}

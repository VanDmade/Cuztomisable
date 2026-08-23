<?php

namespace VanDmade\Cuztomisable\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use VanDmade\Cuztomisable\Services\OrganizationService;

/**
 * Handles listing a user's organizations and switching which one is current.
 */
class OrganizationController extends CuztomisableController
{

    public function __construct(
        protected readonly OrganizationService $organizationService
    ) {
    }

    public function list(Request $request): JsonResponse
    {
        try {
            return $this->success([
                'organizations' => $this->organizationService->list($request->user()),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function current(Request $request): JsonResponse
    {
        try {
            return $this->success([
                'organization' => $request->user()->currentOrganization(),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function switch(Request $request, $id): JsonResponse
    {
        try {
            $organization = $this->organizationService->switch($request->user(), $id);
            return $this->success([
                'message' => __('cuztomisable/organizations.switched'),
                'organization' => $organization,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

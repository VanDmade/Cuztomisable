<?php

namespace VanDmade\Cuztomisable\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use VanDmade\Cuztomisable\Http\Requests\TableRequest;
use VanDmade\Cuztomisable\Http\Requests\TermsRequest;
use VanDmade\Cuztomisable\Services\TermsService;

/**
 * Handles terms & conditions publishing and per-user acceptance.
 */
class TermsController extends CuztomisableController
{

    public function __construct(
        protected readonly TermsService $termsService
    ) {
    }

    public function current(): JsonResponse
    {
        try {
            return $this->success([
                'terms' => $this->termsService->current(),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function accept(Request $request): JsonResponse
    {
        try {
            $acceptance = $this->termsService->accept($request->user());
            return $this->success([
                'message' => __('cuztomisable/terms.accepted'),
                'acceptance' => $acceptance,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function status(Request $request): JsonResponse
    {
        try {
            return $this->success([
                'needs_to_accept' => $this->termsService->needsToAccept($request->user()),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function get($id): JsonResponse
    {
        try {
            return $this->success([
                'terms' => $this->termsService->find($id),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function table(TableRequest $request): JsonResponse
    {
        try {
            return $this->termsService->table($request->validated());
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function acceptances(TableRequest $request): JsonResponse
    {
        try {
            return $this->termsService->acceptanceTable($request->validated());
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function save(TermsRequest $request): JsonResponse
    {
        try {
            $terms = $this->termsService->create($request->validated());
            return $this->success([
                'message' => __('cuztomisable/terms.created'),
                'terms' => $terms,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function publish($id): JsonResponse
    {
        try {
            $terms = $this->termsService->publish($id);
            return $this->success([
                'message' => __('cuztomisable/terms.published'),
                'terms' => $terms,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

<?php

namespace VanDmade\Cuztomisable\Http\Controllers\Users;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use VanDmade\Cuztomisable\Http\Controllers\CuztomisableController;
use VanDmade\Cuztomisable\Http\Requests\TableRequest;
use VanDmade\Cuztomisable\Services\Logs\EmailLogService;
use VanDmade\Cuztomisable\Services\Logs\ErrorLogService;
use VanDmade\Cuztomisable\Services\Logs\TextLogService;
use VanDmade\Cuztomisable\Services\Logs\UserLogService;

/**
 * Admin-only viewer for the four log tables. User/email/text logs are scoped to a single user
 * via the {id} route parameter (each service's table() knows how to filter by it); error logs
 * are viewed globally on their own admin page instead, so no user scoping there.
 */
class LogController extends CuztomisableController
{

    public function __construct(
        protected readonly UserLogService $userLogService,
        protected readonly EmailLogService $emailLogService,
        protected readonly TextLogService $textLogService,
        protected readonly ErrorLogService $errorLogService
    ) {
    }

    public function userLogs(TableRequest $request, $id): JsonResponse
    {
        try {
            return $this->userLogService->table($request->validated(), (int) $id);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function emailLogs(TableRequest $request, $id): JsonResponse
    {
        try {
            return $this->emailLogService->table($request->validated(), (int) $id);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function textLogs(TableRequest $request, $id): JsonResponse
    {
        try {
            return $this->textLogService->table($request->validated(), (int) $id);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function errorLogs(TableRequest $request): JsonResponse
    {
        try {
            // TableRequest's validated() strips anything outside its own known table params, so
            // this doesn't survive there - it has to be merged in separately.
            $data = array_merge($request->validated(), ['show_closed' => $request->boolean('show_closed')]);
            return $this->errorLogService->table($data);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function closeErrors(Request $request, $id): JsonResponse
    {
        try {
            $reason = trim((string) $request->input('reason', ''));
            $closeDuplicates = $request->boolean('close_duplicates');
            $this->errorLogService->close((int) $id, $reason, $closeDuplicates);
            return $this->success([
                'message' => __('cuztomisable/logs.closed'),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}

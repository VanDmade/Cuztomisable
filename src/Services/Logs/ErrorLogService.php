<?php

namespace VanDmade\Cuztomisable\Services\Logs;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;
use VanDmade\Cuztomisable\Events\ErrorOccurred;
use VanDmade\Cuztomisable\Models\Logs\Error;
use VanDmade\Cuztomisable\Services\TableService;

class ErrorLogService
{

    public function log(Throwable $error, string $context, array $parameters = []): Error
    {
        $log = Error::create([
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'parameters' => array_merge(['context' => $context], $parameters),
        ]);
        // Package-owned hook for host apps
        ErrorOccurred::dispatch($log);
        return $log;
    }

    public function table(array $data, ?int $userId = null): JsonResponse
    {
        $query = Error::select(
            'error_logs.id', 'error_logs.user_id', 'ru.name as recipient_name',
            'error_logs.message', 'error_logs.file', 'error_logs.line', 'error_logs.code',
            'error_logs.debug_code', 'error_logs.created_at',
            'error_logs.closed_at', 'error_logs.closed_reason', 'cb.name as closed_by_name')
            ->leftJoin('users as ru', 'ru.id', '=', 'error_logs.user_id')
            ->leftJoin('users as cb', 'cb.id', '=', 'error_logs.closed_by');
        if (!is_null($userId)) {
            $query->where('error_logs.user_id', '=', $userId);
        }
        // Defaults to open errors only
        if (empty($data['show_closed'])) {
            $query->whereNull('error_logs.closed_at');
        }
        $parameters = [
            'allowed_columns' => [
                'error_logs.id', 'error_logs.user_id', 'error_logs.code',
                'error_logs.debug_code', 'error_logs.created_at', 'error_logs.closed_at',
            ],
            'search_columns' => ['error_logs.message', 'error_logs.file', 'error_logs.debug_code', 'ru.name'],
            'default_columns' => ['error_logs.id' => 'desc'],
        ];
        return TableService::generate($query, array_merge($data, $parameters));
    }

    public function close(int $id, string $reason, bool $closeDuplicates = false): void
    {
        $error = Error::find($id);
        if (!$error) {
            throw new Exception(__('cuztomisable/logs.errors.not_found'), 404);
        }
        $ids = [$error->id];
        if ($closeDuplicates) {
            $ids = array_merge($ids, Error::where('message', $error->message)
                ->whereNull('closed_at')
                ->where('id', '!=', $error->id)
                ->pluck('id')
                ->all());
        }
        Error::whereIn('id', $ids)->update([
            'closed_at' => now(),
            'closed_by' => Auth::id(),
            'closed_reason' => $reason,
        ]);
    }

}

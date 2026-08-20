<?php

namespace VanDmade\Cuztomisable\Services\Logs;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use VanDmade\Cuztomisable\Models\Logs\Email;
use VanDmade\Cuztomisable\Services\TableService;

class EmailLogService
{

    public function create(array $data): Email
    {
        if (empty($data['user_id'])) {
            // IF the user ID doesn't exist it will find the first to address and find a user for that
            $to = $data['to'] ?? null;
            $to = $to instanceof Arrayable ? $to->all() : $to;
            $firstTo = Arr::first(Arr::wrap($to));
            if (!empty($firstTo)) {
                $user = config('auth.providers.users.model')::findUserByType($firstTo, 'email');
                $data['user_id'] = $user->id ?? null;
            }
        }
        return Email::create($data);
    }

    public function get($id): Email
    {
        $log = Email::find($id);
        if (!isset($log->id)) {
            throw new Exception(__('cuztomisable/logs.errors.not_found'), 404);
        }
        return $log;
    }

    public function table(array $data): JsonResponse
    {
        $query = Email::select(
            'email_logs.id', 'email_logs.user_id', 'ru.name as recipient_name',
            'email_logs.created_by', 'cu.name as created_by_name',
            'email_logs.to', 'email_logs.cc', 'email_logs.bcc', 'email_logs.from',
            'email_logs.subject', 'email_logs.created_at')
            ->leftJoin('users as ru', 'ru.id', '=', 'email_logs.user_id')
            ->leftJoin('users as cu', 'cu.id', '=', 'email_logs.created_by');
        $parameters = [
            'allowed_columns' => [
                'email_logs.id', 'email_logs.user_id', 'email_logs.created_by',
                'email_logs.from', 'email_logs.subject', 'email_logs.created_at',
            ],
            'search_columns' => ['email_logs.to', 'email_logs.from', 'email_logs.subject', 'ru.name', 'cu.name'],
            'default_columns' => ['email_logs.id' => 'desc'],
        ];
        return TableService::run($query, array_merge($data, $parameters));
    }

}

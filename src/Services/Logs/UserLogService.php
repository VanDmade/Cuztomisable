<?php

namespace VanDmade\Cuztomisable\Services\Logs;

use Illuminate\Http\JsonResponse;
use VanDmade\Cuztomisable\Models\Logs\User;
use VanDmade\Cuztomisable\Services\TableService;

class UserLogService
{

    public function log(int $userId, string $description, array $parameters = []): User
    {
        return User::create([
            'user_id' => $userId,
            'description' => $description,
            'parameters' => $parameters,
        ]);
    }

    public function table(array $data, ?int $userId = null): JsonResponse
    {
        $query = User::select(
            'user_logs.id', 'user_logs.user_id', 'ru.name as recipient_name',
            'user_logs.created_by', 'cu.name as created_by_name',
            'user_logs.description', 'user_logs.created_at')
            ->leftJoin('users as ru', 'ru.id', '=', 'user_logs.user_id')
            ->leftJoin('users as cu', 'cu.id', '=', 'user_logs.created_by');
        if (!is_null($userId)) {
            $query->where('user_logs.user_id', '=', $userId);
        }
        $parameters = [
            'allowed_columns' => [
                'user_logs.id', 'user_logs.user_id', 'user_logs.created_by', 'user_logs.created_at',
            ],
            'search_columns' => ['user_logs.description', 'ru.name', 'cu.name'],
            'default_columns' => ['user_logs.id' => 'desc'],
        ];
        return TableService::generate($query, array_merge($data, $parameters));
    }

}

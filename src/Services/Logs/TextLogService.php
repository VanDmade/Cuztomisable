<?php

namespace VanDmade\Cuztomisable\Services\Logs;

use Exception;
use Illuminate\Http\JsonResponse;
use VanDmade\Cuztomisable\Models\Logs\Text;
use VanDmade\Cuztomisable\Services\PhoneService;
use VanDmade\Cuztomisable\Services\TableService;

class TextLogService
{

    public function __construct(
        protected readonly PhoneService $phoneService
    ) {
    }

    public function create(array $data): Text
    {
        if (empty($data['user_id']) && !empty($data['number'])) {
            // Finds a user that matches that phone number if it wasn't already linked
            $phone = $this->phoneService->findByNumber(
                $data['country_code'] ?? null,
                $data['number']
            );
            $data['user_id'] = $phone->user_id ?? null;
        }
        return Text::create($data);
    }

    public function find($id): Text
    {
        $log = Text::find($id);
        if (!isset($log->id)) {
            throw new Exception(__('cuztomisable/logs.errors.not_found'), 404);
        }
        return $log;
    }

    public function table(array $data): JsonResponse
    {
        $query = Text::select(
            'text_logs.id', 'text_logs.user_id', 'ru.name as recipient_name',
            'text_logs.created_by', 'cu.name as created_by_name',
            'text_logs.country_code', 'text_logs.number', 'text_logs.message',
            'text_logs.created_at')
            ->leftJoin('users as ru', 'ru.id', '=', 'text_logs.user_id')
            ->leftJoin('users as cu', 'cu.id', '=', 'text_logs.created_by');
        $parameters = [
            'allowed_columns' => [
                'text_logs.id', 'text_logs.user_id', 'text_logs.created_by',
                'text_logs.country_code', 'text_logs.number', 'text_logs.created_at',
            ],
            'search_columns' => ['text_logs.number', 'text_logs.message', 'ru.name', 'cu.name'],
            'default_columns' => ['text_logs.id' => 'desc'],
        ];
        return TableService::generate($query, array_merge($data, $parameters));
    }

}

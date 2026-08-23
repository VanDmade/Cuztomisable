<?php

namespace VanDmade\Cuztomisable\Services;

use VanDmade\Cuztomisable\Models\Form;

class FormService
{

    public function find(int $userId, string $page): ?Form
    {
        return Form::where('user_id', $userId)
            ->where('current', '=', $page)
            ->latest('id')
            ->first();
    }

    public function save(int $userId, string $page, array $data): Form
    {
        $payload = [
            'to' => $data['to'] ?? null,
            'to_params' => $this->normalizeJson($data['to_params'] ?? null),
            'current' => $data['current'] ?? $page,
            'current_params' => $this->normalizeJson($data['current_params'] ?? null),
            'form' => $this->normalizeJson($data['form'] ?? null),
        ];
        return Form::updateOrCreate([
            'user_id' => $userId,
            'current' => $payload['current'],
        ], $payload);
    }

    private function normalizeJson(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return json_encode($value);
        }
        return is_string($value) ? $value : json_encode($value);
    }

}

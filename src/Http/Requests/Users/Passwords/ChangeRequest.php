<?php

namespace VanDmade\Cuztomisable\Http\Requests\Users\Passwords;

use Illuminate\Support\Facades\Auth;
use VanDmade\Cuztomisable\Http\Requests\CuztomisableRequest;

class ChangeRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if ($this->missing('invalidate_sessions')) {
            $this->merge([
                'invalidate_sessions' => false,
            ]);
        }
    }

    public function rules(): array
    {
        $requirements = config('cuztomisable.account.passwords.requirements', []);
        $min = (int) ($requirements['min'] ?? 8);
        $max = $requirements['max'] ?? null;
        $numbers = (int) ($requirements['numbers'] ?? 0);
        $uppercase = (int) ($requirements['uppercase_characters'] ?? 0);
        $special = (int) ($requirements['special_characters'] ?? 0);
        $params = [
            'new' => [
                'required',
                'string',
                'min:'.$min,
                $max ? 'max:'.$max : 'nullable',
                function($attribute, $value, $fail) use ($numbers, $uppercase, $special) {
                    if ($numbers > 0 && preg_match_all('/\d/', $value) < $numbers) {
                        return $fail(__('cuztomisable/authentication.passwords.errors.requirements'));
                    }
                    if ($uppercase > 0 && preg_match_all('/[A-Z]/', $value) < $uppercase) {
                        return $fail(__('cuztomisable/authentication.passwords.errors.requirements'));
                    }
                    if ($special > 0 && preg_match_all('/[^A-Za-z0-9]/', $value) < $special) {
                        return $fail(__('cuztomisable/authentication.passwords.errors.requirements'));
                    }
                    return null;
                },
            ],
            'current' => 'required',
            'invalidate_sessions' => 'boolean',
        ];
        if ($this->has('force') && Auth::user()->admin) {
            unset($params['current']);
            $this->merge(['force' => true]);
            $params['force'] = 'required|boolean';
        }
        return $params;
    }

}

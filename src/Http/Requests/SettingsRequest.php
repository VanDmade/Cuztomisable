<?php

namespace VanDmade\Cuztomisable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use VanDmade\Cuztomisable\Services\SettingsService;

/**
 * Validates saving an admin-editable setting.
 */
class SettingsRequest extends FormRequest
{

    public function authorize(): bool
    {
        $key = $this->input('key');
        if (!in_array($key, config('cuztomisable.settings', []))) {
            return false;
        }
        return Auth::check() && app(SettingsService::class)->canManage(Auth::user(), $key);
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', Rule::in(config('cuztomisable.settings', []))],
            'value' => ['required', 'string'],
        ];
    }

}

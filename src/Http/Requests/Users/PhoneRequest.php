<?php

namespace VanDmade\Cuztomisable\Http\Requests\Users;

use VanDmade\Cuztomisable\Http\Requests\CuztomisableRequest;

class PhoneRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'number' => strval(cleanPhone($this->input('number'))),
        ]);
    }

    public function rules(): array
    {
        $list = config('cuztomisable.locations.country_codes') ?? [];
        foreach ($list as $row) {
            if ($row['value'] == $this->input('country_code', 1)) {
                $size = $row['required_length'] ?? 10;
            }
        }
        return [
            'number' => 'required|size:'.($size ?? 10),
            'country_code' => 'nullable',
            'extension' => 'nullable|string|max:10',
            'mobile' => 'nullable|in:0,1',
            'disable_messages' => 'nullable|in:0,1',
        ];
    }

}

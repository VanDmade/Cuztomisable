<?php

namespace VanDmade\Cuztomisable\Http\Requests;

use Illuminate\Support\Facades\Auth;

class TimezoneRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        // Only logged in users can update their current timezone
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'timezone' => 'required|string|timezone',
        ];
    }

}

<?php

namespace VanDmade\Cuztomisable\Http\Requests;

use Illuminate\Support\Facades\Auth;

/**
 * Validates the browser-detected timezone sync.
 */
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

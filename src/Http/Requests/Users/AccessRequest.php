<?php

namespace VanDmade\Cuztomisable\Http\Requests\Users;

use VanDmade\Cuztomisable\Http\Requests\CuztomisableRequest;

class AccessRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roles' => 'nullable|array',
            'roles.*' => 'nullable|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'nullable|exists:permissions,id',
        ];
    }

}

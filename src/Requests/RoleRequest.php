<?php

namespace VanDmade\Cuztomisable\Requests;

use VanDmade\Cuztomisable\Requests\BaseRequest;

class RoleRequest extends BaseRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? null;
        return [
            'name' => 'required',
            'slug' => 'required|unique:roles,slug,'.$id.',id',
            'description' => 'nullable',
            'permissions' => 'nullable|array',
            'permissions.*' => 'nullable|exists:permissions,id',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => trim((string) $this->input('slug')),
        ]);
    }
}

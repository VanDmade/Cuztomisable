<?php

namespace VanDmade\Cuztomisable\Http\Requests;

class PermissionRequest extends CuztomisableRequest
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
            'slug' => 'required|unique:permissions,slug,'.$id.',id',
            'description' => 'nullable',
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

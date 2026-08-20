<?php

namespace VanDmade\Cuztomisable\Http\Requests;

use VanDmade\Cuztomisable\Services\TableService;

/**
 * Validation rules for paginated/filtered/sorted table-listing requests - renamed from
 * TablelifyRequest to match Cuztomisable-native naming (Step 5's Tablelify fold-in).
 */
class TableRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'integer' => __('cuztomisable/global.form.integer'),
            'between' => __('cuztomisable/global.form.between'),
            'array' => __('cuztomisable/global.form.array'),
            'in' => __('cuztomisable/global.form.in'),
        ]);
    }

    public function prepareForValidation(): void
    {
        // Cleans and sets the appropriate fields that are 100% needed to validate prior
        $parameters = TableService::cleanRequest([
            'page' => $this->page ?? 1,
            'size' => $this->size ?? config('cuztomisable.tablelify.default.size', 10),
            'column' => $this->column ?? null,
            'columns' => $this->columns ?? null,
            'direction' => $this->direction ?? null,
            'search' => $this->search ?? '',
            'filters' => $this->filters ?? [],
        ]);
        // Secondary cleans the parameters to make sure they meet the needs of TableService
        $parameters = [
            // The page must be one if the user is looking to see all of the data in one view
            'page' => $parameters['size'] == 'all' ? 1 : ($parameters['page'] ?? 1),
            'size' => $parameters['size'] ?? config('cuztomisable.tablelify.default.size', 10),
            'column' => $parameters['column'],
            'columns' => $parameters['columns'],
            'direction' => !in_array($parameters['direction'], ['asc', 'desc', null]) ? null : $parameters['direction'],
            'search' => empty($parameters['search']) ? null : $parameters['search'],
            'filters' => $parameters['filters'] ?? [],
        ];
        // Removes the filters if for some reason they are not sent
        if (empty($parameters['filters'])) {
            unset($parameters['filters']);
        }
        // Only allows the list of columns through and not the singular column
        if (!empty($parameters['columns'])) {
            unset($parameters['column'], $parameters['direction']);
        } else {
            unset($parameters['columns']);
        }
        $this->merge($parameters);
    }

    public function rules(): array
    {
        return [
            'page' => 'required|integer|min:1',
            // If the size is set to "All" it will allow the developer to easily not put a limit on the list
            'size' => 'required|'.(strtolower($this->size) != 'all' ?
                'integer|between:1,'.(int) config('cuztomisable.tablelify.max_size', 100) : 'in:all'),
            'column' => 'nullable',
            'direction' => 'nullable|in:asc,desc',
            'search' => 'nullable',
            'filters' => 'nullable|array',
            // Allows for either the specific column and direction OR a list of columns
            'columns' => 'nullable|array',
            'columns.*.column' => 'required',
            'columns.*.direction' => 'required|in:asc,desc',
            // Represents the key that is used by the developer to specify a specific filter/value combination
            'filters.*.key' => 'required',
            'filters.*.value' => 'nullable',
        ];
    }

}

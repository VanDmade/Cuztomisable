<?php

namespace VanDmade\Cuztomisable\Http\Requests;

use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use VanDmade\Cuztomisable\Models\Questionnaire\Question;
use VanDmade\Cuztomisable\Services\QuestionnaireService;

/**
 * Validates a questionnaire question being added or edited in the admin area - its type, text keys, choices, where
 * the answer is written, and whether it's required and in use.
 */
class QuestionnaireQuestionRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();
        if (isset($data['key']) && is_string($data['key'])) {
            $data['key'] = self::normalizeKey($data['key']);
        }
        foreach (['subtitle', 'description', 'image', 'source', 'target_table', 'target_column', 'other_label', 'placeholder'] as $key) {
            if (array_key_exists($key, $data) && (is_null($data[$key]) || trim((string) $data[$key]) === '')) {
                $data[$key] = null;
            }
        }
        // Blank choice rows are dropped
        if (isset($data['options']) && is_array($data['options'])) {
            $data['options'] = array_values(array_filter($data['options'], fn($o) => trim((string) ($o['value'] ?? '')) !== ''));
        }
        $this->replace($data);
    }

    /** "Favorite Drink!" -> favorite_drink: lowercase letters, numbers and single underscores. */
    public static function normalizeKey(string $key): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($key))), '_');
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/', Rule::unique('questionnaire_questions', 'key')->ignore($id)],
            'order' => ['required', 'integer', 'min:0'],
            'type' => ['required', Rule::in(Question::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            // The image already on the question (kept as-is), or a new one uploaded in its place
            'image' => ['nullable', 'string', 'max:255'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'source' => ['nullable', 'string', Rule::in(array_keys((array) config('cuztomisable.questionnaire.sources', [])))],
            'options' => ['nullable', 'array'],
            'options.*.value' => ['required', 'string', 'max:255'],
            'options.*.label' => ['required', 'string', 'max:255'],
            'allow_other' => ['boolean'],
            'other_label' => ['nullable', 'string', 'max:255'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'min' => ['nullable', 'numeric'],
            'max' => ['nullable', 'numeric'],
            'target_table' => ['nullable', 'string', Rule::in(array_keys(app(QuestionnaireService::class)->allowedTables()))],
            'target_column' => ['nullable', 'required_with:target_table', 'string', 'max:64'],
            'target_key' => ['required', 'string', 'max:64'],
            'required' => ['boolean'],
            'active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function(Validator $validator) {
            $type = $this->input('type');
            $choices = in_array($type, ['select', 'multi_select'], true);
            if ($choices && !$this->input('source') && empty($this->input('options'))) {
                $validator->errors()->add('options', __('cuztomisable/questionnaire.admin.errors.choices_required'));
            }
            $table = $this->input('target_table');
            $column = $this->input('target_column');
            // Only the columns listed for the table in allowed_tables
            if ($table && $column && !app(QuestionnaireService::class)->allowedColumn($table, $column)) {
                $validator->errors()->add('target_column', __('cuztomisable/questionnaire.admin.errors.column_not_allowed', ['table' => $table]));
            }
            if ($table && Schema::hasTable($table)) {
                foreach (['target_column', 'target_key'] as $field) {
                    if ($this->input($field) && !Schema::hasColumn($table, $this->input($field))) {
                        $validator->errors()->add($field, __('cuztomisable/questionnaire.admin.errors.column_missing', ['table' => $table]));
                    }
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'options.*.value' => __('cuztomisable/questionnaire.admin.attributes.option_value'),
            'options.*.label' => __('cuztomisable/questionnaire.admin.attributes.option_label'),
            'target_table' => __('cuztomisable/questionnaire.admin.attributes.target_table'),
            'target_column' => __('cuztomisable/questionnaire.admin.attributes.target_column'),
            'target_key' => __('cuztomisable/questionnaire.admin.attributes.target_key'),
        ];
    }

}

<?php

namespace VanDmade\Cuztomisable\Models\Questionnaire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One sign-up questionnaire question. Its text fields hold language keys, and target_table / target_column say where
 * the answer is also written on the user's record.
 */
class Question extends Model
{

    public const TYPES = ['boolean', 'select', 'multi_select', 'text', 'number', 'date'];

    protected $table = 'questionnaire_questions';

    protected $fillable = [
        'key',
        'order',
        'type',
        'title',
        'subtitle',
        'description',
        'image',
        'options',
        'source',
        'settings',
        'target_table',
        'target_column',
        'target_key',
        'required',
        'active',
    ];

    protected $casts = [
        'options' => 'array',
        'settings' => 'array',
        'required' => 'boolean',
        'active' => 'boolean',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'questionnaire_question_id');
    }

    public function setting(string $name, mixed $default = null): mixed
    {
        return $this->settings[$name] ?? $default;
    }

}

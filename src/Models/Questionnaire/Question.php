<?php

namespace VanDmade\Cuztomisable\Models\Questionnaire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VanDmade\Cuztomisable\Concerns\SoftDeletes;

/**
 * One questionnaire question. Its text fields hold language keys, and target_table / target_column say where the
 * answer is also written on the user's record. Deleting one is a soft delete, so the answers to it stay on record.
 */
class Question extends Model
{

    use SoftDeletes;

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
        'deleted_at' => 'datetime',
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

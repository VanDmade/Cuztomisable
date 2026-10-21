<?php

namespace VanDmade\Cuztomisable\Models\Questionnaire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's answer to a questionnaire question, as they first gave it. The question's target column holds the
 * current value, so the two can be compared later (e.g. "ran 1 mile a week then, 10 now").
 */
class Answer extends Model
{

    protected $table = 'questionnaire_answers';

    protected $fillable = [
        'user_id',
        'questionnaire_question_id',
        'value',
        'other',
    ];

    protected $casts = [
        'value' => 'json',
        'other' => 'boolean',
    ];

    protected $hidden = [
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'questionnaire_question_id');
    }

}

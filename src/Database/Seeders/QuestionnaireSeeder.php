<?php

namespace VanDmade\Cuztomisable\Database\Seeders;

use Illuminate\Database\Seeder;
use VanDmade\Cuztomisable\Models\Questionnaire\Question;

/**
 * Loads the questions defined in config('cuztomisable.questionnaire.questions') into the questions table, matched by
 * key and ordered as they're listed. Safe to re-run - existing questions are updated, and questions added in the admin
 * area that aren't in the config are left alone.
 *
 *   php artisan db:seed --class="VanDmade\Cuztomisable\Database\Seeders\QuestionnaireSeeder"
 */
class QuestionnaireSeeder extends Seeder
{

    public function run(): void
    {
        $questions = (array) config('cuztomisable.questionnaire.questions', []);
        $position = 0;
        foreach ($questions as $key => $question) {
            // Keyed by the question's key, or listed with a 'key' of its own
            $key = is_string($key) ? $key : ($question['key'] ?? null);
            if (!$key) {
                continue;
            }
            $position++;
            Question::updateOrCreate(['key' => $key], [
                'order' => $position,
                'type' => $question['type'] ?? 'text',
                'title' => $question['title'] ?? $key,
                'subtitle' => $question['subtitle'] ?? null,
                'description' => $question['description'] ?? null,
                'image' => $question['image'] ?? null,
                'options' => $question['options'] ?? null,
                'source' => $question['source'] ?? null,
                'settings' => $question['settings'] ?? null,
                'target_table' => $question['target_table'] ?? null,
                'target_column' => $question['target_column'] ?? null,
                'target_key' => $question['target_key'] ?? (($question['target_table'] ?? null) === 'users' ? 'id' : 'user_id'),
                'required' => $question['required'] ?? true,
                'active' => $question['active'] ?? true,
            ]);
        }
    }

}

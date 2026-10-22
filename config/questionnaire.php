<?php

// The questionnaire. Merged into config('cuztomisable.questionnaire'). The questions live in the questionnaire_questions
// table - list them under 'questions' here and the package's QuestionnaireSeeder loads them, or add them in the admin
// area. See docs/13-questionnaire.md.
return [
    // Off by default - turn it on once the host app has seeded its questions
    'enabled' => env('CUZTOMISABLE_QUESTIONNAIRE', false),
    // Block every other API call (via the require-questionnaire middleware) until the required questions are answered
    'required' => true,
    // Named lists of choices that come from the app's own data instead of being fixed on the question, e.g.
    // 'drinks' => App\Questionnaire\Sources\Drinks::class. Each class implements
    // VanDmade\Cuztomisable\Questionnaire\OptionSource.
    'sources' => [],
    // The only tables and columns answers may be written into - anything not listed is never touched, whatever a
    // question row says. e.g. ['users' => ['over_21', 'favorite_drink_id'], 'runner_profiles' => ['weekly_miles']]
    'allowed_tables' => [],
    // The questions, keyed by question key and asked in the order listed - loaded into the database by
    // VanDmade\Cuztomisable\Database\Seeders\QuestionnaireSeeder. Text values are language keys (or plain text), e.g.
    // 'over_21' => ['type' => 'boolean', 'title' => 'questionnaire.over_21.title', 'target_table' => 'users',
    //     'target_column' => 'over_21']
    'questions' => [],
];

<?php

// The sign-up questionnaire. Merged into config('cuztomisable.questionnaire'). The questions themselves live in the
// questionnaire_questions table (seed them from the host app) - see docs/13-questionnaire.md.
return [
    // Off by default - turn it on once the host app has seeded its questions
    'enabled' => env('CUZTOMISABLE_QUESTIONNAIRE', false),
    // Block every other API call (via the require-questionnaire middleware) until the required questions are answered
    'required' => true,
    // The intro shown before the first question. Text values are language keys, so each language gets its own copy.
    'title' => 'cuztomisable/questionnaire.intro.title',
    'subtitle' => 'cuztomisable/questionnaire.intro.subtitle',
    'description' => 'cuztomisable/questionnaire.intro.description',
    'image' => null,
    // Named lists of choices that come from the app's own data instead of being fixed on the question, e.g.
    // 'drinks' => App\Questionnaire\Sources\Drinks::class. Each class implements
    // VanDmade\Cuztomisable\Questionnaire\OptionSource.
    'sources' => [],
    // The only tables answers may be written into. Keeps a bad question row from writing anywhere else.
    'writable_tables' => ['users'],
];

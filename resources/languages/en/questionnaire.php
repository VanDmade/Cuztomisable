<?php

return [
    'other' => 'I can\'t find it',
    'saved' => 'Your answers were saved.',
    'completed' => 'Thanks - you\'re all set!',
    'admin' => [
        'saved' => 'The question was saved.',
        'reordered' => 'The questions were reordered.',
        'deleted' => 'The question was deleted. Answers already given to it are kept.',
        'errors' => [
            'choices_required' => 'Add at least one choice, or pick where the choices come from.',
            'column_missing' => 'The :table table has no column with that name.',
            'column_not_allowed' => 'Answers can\'t be saved to that column of :table - it isn\'t listed in the questionnaire\'s allowed_tables.',
            'image_failed' => 'The image could not be uploaded.',
        ],
        'attributes' => [
            'option_value' => 'choice value',
            'option_label' => 'choice label',
            'target_table' => 'table',
            'target_column' => 'column',
            'target_key' => 'ID',
        ],
    ],
    'errors' => [
        'must_complete' => 'Please finish the questionnaire before continuing.',
        'unknown_question' => 'That question doesn\'t exist.',
        'unknown_source' => 'The ":source" list of choices isn\'t set up.',
        'invalid_target' => 'The ":key" question is set to save somewhere it isn\'t allowed to.',
        'required' => 'Please answer this question.',
        'invalid_type' => 'This question can\'t be answered right now.',
        'invalid_boolean' => 'Please answer yes or no.',
        'invalid_number' => 'Please enter a number.',
        'out_of_range' => 'Please enter a number between :min and :max.',
        'invalid_date' => 'Please enter a valid date.',
        'invalid_text' => 'Please enter some text.',
        'too_long' => 'Please keep this under :max characters.',
        'invalid_choice' => 'Please pick one of the choices.',
    ],
];

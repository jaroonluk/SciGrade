<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Thesis / Independent Study grade submission URL
    |--------------------------------------------------------------------------
    |
    | Optional override for the yellow homepage button.
    | Leave empty to use the built-in /thesis-grades workspace.
    |
    */
    'thesis_grade_url' => env('THESIS_GRADE_URL', ''),

    'reg_url' => env('SCIGRADE_REG_URL', 'https://reg.kku.ac.th'),

    's0_letter_form_url' => env('SCIGRADE_S0_LETTER_FORM_URL', 'https://kku.world/cq7gj'),
];

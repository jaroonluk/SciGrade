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

    /*
    | Before go-live, thesis-grade submit mail goes here instead of real dept admins.
    | Set SCIGRADE_DEPT_ADMIN_MAIL_OVERRIDE= (empty) to send to actual department admins.
    */
    'dept_admin_mail_override' => env('SCIGRADE_DEPT_ADMIN_MAIL_OVERRIDE', 'jaroonluk@kku.ac.th'),
];

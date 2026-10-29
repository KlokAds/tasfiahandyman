<?php

/*
| Content AI (Admin → Content → Content AI): turns photos of a finished job and a short note into a
| draft article and social media captions. Only the accounts listed here can open it.
*/
return [
    // Comma-separated sign-in emails that may use it. Nobody else sees the menu or the page.
    'emails' => array_values(array_filter(array_map('trim', explode(',', (string) env('CONTENT_AI_EMAILS', 'mujxaid@gmail.com'))))),

    'model' => env('CONTENT_AI_MODEL', 'claude-sonnet-5-5'),

    // Price per 1M tokens (USD) of the model above, to show what each draft cost.
    'price' => ['in' => 2.00, 'out' => 10.00],

    // At most this many drafts a day, so articles are never mass-produced.
    'daily_limit' => (int) env('CONTENT_AI_DAILY_LIMIT', 10),

    // Photos sent to the AI per job (the page shrinks them before upload).
    'max_photos' => 8,
];

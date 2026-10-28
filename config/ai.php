<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    | Options: "gemini", "openai", "mock"
    */
    'provider' => env('AI_PROVIDER', 'gemini'),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash'),
        'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
        'temperature' => 0.2,
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'endpoint' => 'https://api.openai.com/v1/chat/completions',
        'temperature' => 0.2,
    ],
];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
        'ticket_classification_enabled' => env('AI_TICKET_CLASSIFICATION_ENABLED', true),
        'assistant_enabled' => env('OPENAI_ASSISTANT_ENABLED', true),
        'assistant_model' => env('OPENAI_ASSISTANT_MODEL', env('OPENAI_MODEL', 'gpt-4.1-mini')),
        'assistant_vector_store_id' => env('OPENAI_ASSISTANT_VECTOR_STORE_ID'),
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'openai'),
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.2:3b'),
        'assistant_enabled' => env('OLLAMA_ASSISTANT_ENABLED', true),
    ],

];

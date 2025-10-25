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

    /*
    |--------------------------------------------------------------------------
    | Microservicios
    |--------------------------------------------------------------------------
    */

    'recepcion' => [
        'url' => env('MS_RECEPCION_URL', 'http://localhost:8000'),
        'timeout' => env('MS_RECEPCION_TIMEOUT', 10),
    ],

    'decision' => [
        'url' => env('MS_DECISION_URL', 'http://localhost:8002'),
        'timeout' => env('MS_DECISION_TIMEOUT', 10),
        'webhook_endpoint' => env('MS_DECISION_WEBHOOK_ENDPOINT', '/api/webhook/despacho'),
    ],

    'auth' => [
        'url' => env('MS_AUTH_URL', 'http://localhost:8003'),
        'timeout' => env('MS_AUTH_TIMEOUT', 10),
        'verify_endpoint' => env('MS_AUTH_VERIFY_ENDPOINT', '/api/verify-token'),
    ],

    'websocket' => [
        'url' => env('MS_WEBSOCKET_URL', 'http://localhost:3000'),
        'timeout' => env('MS_WEBSOCKET_TIMEOUT', 5),
        'enabled' => env('MS_WEBSOCKET_ENABLED', true),
    ],

    'ml' => [
        'url' => env('ML_SERVICE_URL', 'http://localhost:5000'),
        'timeout' => env('ML_SERVICE_TIMEOUT', 10),
        'use_fallback' => env('ML_USE_FALLBACK', true),
    ],

];

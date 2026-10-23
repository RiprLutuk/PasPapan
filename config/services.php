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

    'vercel' => [
        'maintenance_endpoint_enabled' => env('VERCEL_MAINTENANCE_ENDPOINT_ENABLED', false),
        'maintenance_token' => env('VERCEL_MAINTENANCE_TOKEN', ''),
        'allow_web_seed' => env('VERCEL_ALLOW_WEB_SEED', false),
    ],

    'e2e' => [
        // This hook is disabled unless an operator explicitly supplies a secret.
        'login_token' => env('E2E_LOGIN_TOKEN', ''),
    ],

    'whatsapp' => [
        'support_number' => env('WHATSAPP_SUPPORT_NUMBER') ?: base64_decode('NjI4MjMyNDc3NDM4MA=='),
    ],

    'attendance_integration' => [
        'api_key' => env('ATTENDANCE_INTEGRATION_API_KEY', ''),
        'secret' => env('ATTENDANCE_INTEGRATION_SECRET', ''),
        'signature_tolerance_seconds' => env('ATTENDANCE_INTEGRATION_SIGNATURE_TOLERANCE_SECONDS', 300),
        'allowed_sources' => array_values(array_filter(array_map(
            static fn (string $source): string => trim($source),
            explode(',', (string) env('ATTENDANCE_INTEGRATION_ALLOWED_SOURCES', '')),
        ))),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];

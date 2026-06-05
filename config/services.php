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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'greenjade' => [
        'base_url'       => env('GREENJADE_BASE_URL'),
        'service_code'   => env('GREENJADE_SERVICE_CODE', 'muh5'),
        'service_secret' => env('GREENJADE_SERVICE_SECRET'),
    ],

    'greenjade_id' => [
        'base_url' => env('GREENJADE_ID_BASE_URL'),
        'client_id' => env('GREENJADE_ID_CLIENT_ID'),
        'client_secret' => env('GREENJADE_ID_CLIENT_SECRET'),
        'redirect_uri' => env('GREENJADE_ID_REDIRECT_URI'),
    ],

];

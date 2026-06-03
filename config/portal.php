<?php

return [
    'url' => env('PORTAL_URL'),
    'api_url' => env('PORTAL_API_URL'),
    'game_code' => env('PORTAL_GAME_CODE', 'muh5'),
    'game_secret' => env('PORTAL_GAME_SECRET'),
    'muh5_launch_secret' => env('MUH5_LAUNCH_SECRET') ?: env('CCGAME_LAUNCH_SECRET'),
    'api_secret' => env('PORTAL_API_SECRET'),
    'exchange_rate' => env('PORTAL_EXCHANGE_RATE', 1000),
    'max_exchange_per_request' => env('PORTAL_MAX_EXCHANGE', 10000),
];

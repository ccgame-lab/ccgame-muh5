<?php

return [
    'server_open' => env('SERVER_OPEN', false),
    'allowed_usernames' => array_filter(array_map('trim', explode(',', env('ALLOWED_USERNAMES', '')))),

    'game' => [
        'name' => env('GAME_NAME', 'MU Archangel H5'),
        'code' => env('GAME_CODE', 'muh5'),
        'play_url' => env('GAME_PLAY_URL'),
        'cdn_url' => env('GAME_CDN_URL'),
    ],

    'website' => [
        'title' => env('WEBSITE_TITLE', 'MU Archangel H5 - Game H5 Đa Nền Tảng Mới Nhất 2026'),
        'description' => env('WEBSITE_DESCRIPTION', 'MU Archangel H5 - Game H5 mới nhất 2026 đỉnh cao, game H5 đa nền tảng chơi được cả trên mobile và pc.'),
        'keywords' => env('WEBSITE_KEYWORDS', 'muh5 online, game h5, mu archangel h5'),
        'og_image' => env('WEBSITE_OG_IMAGE', '/assets/home/images/logo.png'),
        'url' => env('APP_URL'),
    ],

    'facebook' => [
        'fanpage_url' => env('FACEBOOK_FANPAGE_URL', 'https://facebook.com/gcenter.vn'),
        'group_url' => env('FACEBOOK_GROUP_URL', 'https://facebook.com/groups/gcenter.vn'),
    ],

    'portal' => [
        'url' => env('PORTAL_URL', 'https://id.greenjade.net'),
        'api_url' => env('PORTAL_API_URL'),
        'game_code' => env('PORTAL_GAME_CODE', 'muh5'),
        'game_secret' => env('PORTAL_GAME_SECRET'),
        'api_secret' => env('PORTAL_API_SECRET'),
        'exchange_rate' => env('PORTAL_EXCHANGE_RATE', 1000),
    ],

    'game_db' => [
        'host' => env('GAME_DB_HOST', '127.0.0.1'),
        'port' => env('GAME_DB_PORT', 3306),
        'username' => env('GAME_DB_USERNAME', 'root'),
        'password' => env('GAME_DB_PASSWORD', ''),
    ],
];

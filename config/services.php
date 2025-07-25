<?php

return [

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

    'replicate' => [
        'token' => env('REPLICATE_API_TOKEN'),
        'version' => env('REPLICATE_MODEL_VERSION'),
    ],

    // ✅ Bloco da Megvii adicionado corretamente:
    'megvii' => [
        'key' => env('MEGVI_API_KEY'),
        'secret' => env('MEGVI_API_SECRET'),
    ],

];

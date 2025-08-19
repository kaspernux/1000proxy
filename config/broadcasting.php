<?php

// Build Pusher options: only include host/port/scheme when self-hosting
$pusherOptions = [
    'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
    'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
];

$customHost = env('PUSHER_HOST');
if (!empty($customHost)) {
    $pusherOptions['host'] = $customHost;
    $pusherOptions['port'] = env('PUSHER_PORT', 6001);
    $pusherOptions['scheme'] = env('PUSHER_SCHEME', 'https');
}

return [
    'default' => env('BROADCAST_DRIVER', 'null'),

    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => $pusherOptions,
            'client_options' => [
                // 'curl_options' => [CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0],
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],
];

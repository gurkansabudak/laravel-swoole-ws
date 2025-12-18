<?php

return [
    'host' => env('WS_HOST', '0.0.0.0'),
    'port' => (int)env('WS_PORT', 9502),

    'pid_file' => storage_path('ws/ws.pid'),

    'routes_file' => base_path('routes/ws.php'),
    'channels_file' => base_path('routes/ws_channels.php'),

    'server' => [
        'worker_num' => (int)env('WS_WORKER_NUM', 2),
        'task_worker_num' => (int)env('WS_TASK_WORKER_NUM', 0),
        'max_request' => (int)env('WS_MAX_REQUEST', 2000),
        'dispatch_mode' => 2,
        'daemonize' => (int)env('WS_DAEMONIZE', 0),
        'log_file' => storage_path('logs/swoole-ws.log'),
        'open_websocket_ping_frame' => true,
        'websocket_ping_interval' => (int)env('WS_PING_INTERVAL', 20),
        'websocket_ping_timeout' => (int)env('WS_PING_TIMEOUT', 60),
    ],

    'middleware' => [
        // global middleware applied to every WS route
        // \App\Ws\Middleware\Authenticate::class,
    ],

    'store' => [
        'driver' => env('WS_STORE', 'table'), // memory|table
        'table' => [
            'size' => 4096,
        ],
    ],

    'auth' => [
        'guard' => env('WS_AUTH_GUARD', 'api'),
        'token_input_key' => 'auth', // meta.auth
    ],
];

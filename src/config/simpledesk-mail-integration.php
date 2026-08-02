<?php

return [
    'mailpit' => [
        'base_url' => env(
            'MAILPIT_API_URL',
            'http://mailpit:8025'
        ),

        'username' => env('MAILPIT_API_USERNAME'),
        'password' => env('MAILPIT_API_PASSWORD'),

        'http_timeout_seconds' => env(
            'MAILPIT_API_TIMEOUT_SECONDS',
            10
        ),

        'delivery_timeout_seconds' => env(
            'MAILPIT_DELIVERY_TIMEOUT_SECONDS',
            30
        ),

        'poll_interval_milliseconds' => env(
            'MAILPIT_POLL_INTERVAL_MILLISECONDS',
            250
        ),
    ],
];

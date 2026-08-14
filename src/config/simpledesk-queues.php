<?php

use App\Services\Admin\System\Queues\Drivers\DatabaseQueueDriverAdapter;
use App\Services\Admin\System\Queues\Drivers\RedisQueueDriverAdapter;
use App\Services\Admin\System\Queues\Drivers\SyncQueueDriverAdapter;

return [
    'adapters' => [
        'database' => DatabaseQueueDriverAdapter::class,
        'redis' => RedisQueueDriverAdapter::class,
        'sync' => SyncQueueDriverAdapter::class,
    ],

    'runtime' => [
        'connection_name' => 'simpledesk-managed',
    ],

    'worker' => [
        'max_timeout_seconds' => 300,
        'retry_safety_margin_seconds' => 30,
    ],

    'database' => [
        'allowed_connections' => array_values(
            array_filter(
                array_map(
                    'trim',
                    explode(
                        ',',
                        (string) env(
                            'SIMPLEDESK_QUEUE_DATABASE_CONNECTIONS',
                            '',
                        ),
                    ),
                ),
                fn (string $value): bool => $value !== '',
            ),
        ),
    ],

    'defaults' => [
        'retry_after' => 360,
        'redis_block_for' => 5,
        'after_commit' => false,
    ],
];

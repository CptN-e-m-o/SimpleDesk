<?php

use App\Services\Admin\System\Cache\Drivers\DatabaseCacheDriverAdapter;
use App\Services\Admin\System\Cache\Drivers\FileCacheDriverAdapter;
use App\Services\Admin\System\Cache\Drivers\RedisCacheDriverAdapter;

return [
    'adapters' => [
        'database' => DatabaseCacheDriverAdapter::class,
        'file' => FileCacheDriverAdapter::class,
        'redis' => RedisCacheDriverAdapter::class,
    ],
    'runtime' => ['store_name' => 'simpledesk-managed'],
    'deployment' => ['store' => env('CACHE_STORE', 'database')],
    'database' => [
        'allowed_connections' => array_values(array_filter(array_map('trim', explode(',', (string) env('SIMPLEDESK_CACHE_DATABASE_CONNECTIONS', ''))))),
    ],
    'health' => ['ttl_seconds' => 30, 'lock_seconds' => 10],
];

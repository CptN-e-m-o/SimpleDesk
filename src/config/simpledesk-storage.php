<?php

use App\Services\Admin\System\Storage\Drivers\LocalStorageDriverAdapter;
use App\Services\Admin\System\Storage\Drivers\S3CompatibleStorageDriverAdapter;
use App\Services\Admin\System\Storage\Drivers\S3StorageDriverAdapter;

return [
    'runtime' => [
        'disk_name' => 'simpledesk-managed',
    ],

    'deployment' => [
        'disk' => env('FILESYSTEM_DISK', 'local'),
    ],

    'local' => [
        'root' => storage_path('app/private'),
    ],

    'health' => [
        's3_connect_timeout_seconds' => 2.0,
        's3_request_timeout_seconds' => 5.0,
        's3_retries' => 1,
    ],

    'adapters' => [
        'local' => LocalStorageDriverAdapter::class,
        's3' => S3StorageDriverAdapter::class,
        's3_compatible' => S3CompatibleStorageDriverAdapter::class,
    ],
];

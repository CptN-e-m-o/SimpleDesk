<?php

use App\Services\Admin\System\Storage\Drivers\LocalStorageDriverAdapter;
use App\Services\Admin\System\Storage\Drivers\S3CompatibleStorageDriverAdapter;
use App\Services\Admin\System\Storage\Drivers\S3StorageDriverAdapter;

return [
    'runtime' => ['disk_name' => 'simpledesk-managed'],
    'deployment' => ['disk' => env('FILESYSTEM_DISK', 'local')],
    'local' => ['root' => storage_path('app/private')],
    'adapters' => [
        'local' => LocalStorageDriverAdapter::class,
        's3' => S3StorageDriverAdapter::class,
        's3_compatible' => S3CompatibleStorageDriverAdapter::class,
    ],
];

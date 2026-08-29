<?php

namespace App\Services\Admin\System\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;

class StorageFilesystemFactory
{
    public function __construct(
        private readonly FilesystemManager $filesystems,
    ) {}

    public function build(array $configuration): Filesystem
    {
        return $this->filesystems->build($configuration);
    }

    public function buildForHealth(array $configuration): Filesystem
    {
        if (($configuration['driver'] ?? null) === 's3') {
            $http = is_array($configuration['http'] ?? null)
                ? $configuration['http']
                : [];

            $configuration['http'] = [
                ...$http,
                'connect_timeout' => (float) config(
                    'simpledesk-storage.health.s3_connect_timeout_seconds',
                    2.0,
                ),
                'timeout' => (float) config(
                    'simpledesk-storage.health.s3_request_timeout_seconds',
                    5.0,
                ),
            ];

            $configuration['retries'] = (int) config(
                'simpledesk-storage.health.s3_retries',
                1,
            );
        }

        return $this->filesystems->build($configuration);
    }
}

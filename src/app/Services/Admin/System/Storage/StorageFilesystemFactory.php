<?php

namespace App\Services\Admin\System\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;

class StorageFilesystemFactory
{
    public function __construct(private readonly FilesystemManager $filesystems) {}

    public function build(array $configuration): Filesystem
    {
        return $this->filesystems->build($configuration);
    }
}

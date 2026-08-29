<?php

namespace App\Data\Admin\System\Storage;

use App\Enums\Admin\System\StorageDriverType;

final readonly class StorageRuntimeConfigurationData
{
    public function __construct(public StorageDriverType $driver, public array $disk) {}
}

<?php

namespace App\Services\Admin\System\Storage\Drivers;

use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\StorageDriverType;
use App\Models\Admin\System\InfrastructureConnection;

class S3CompatibleStorageDriverAdapter extends ExternalStorageDriverAdapter
{
    public function type(): StorageDriverType
    {
        return StorageDriverType::S3Compatible;
    }

    protected function label(): string
    {
        return 'S3-compatible';
    }

    protected function infrastructureType(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::S3Compatible;
    }

    protected function disk(InfrastructureConnection $connection, string $prefix): array
    {
        $value = $connection->getAttribute('configuration');
        $configuration = is_array($value) ? $value : [];

        return [...$this->baseDisk($connection, $prefix), 'endpoint' => $configuration['endpoint'] ?? null, 'use_path_style_endpoint' => (bool) ($configuration['use_path_style_endpoint'] ?? false)];
    }
}

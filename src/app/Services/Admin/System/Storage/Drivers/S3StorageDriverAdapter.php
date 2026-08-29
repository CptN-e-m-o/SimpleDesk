<?php

namespace App\Services\Admin\System\Storage\Drivers;

use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\StorageDriverType;
use App\Models\Admin\System\InfrastructureConnection;

class S3StorageDriverAdapter extends ExternalStorageDriverAdapter
{
    public function type(): StorageDriverType
    {
        return StorageDriverType::S3;
    }

    protected function label(): string
    {
        return 'Amazon S3';
    }

    protected function infrastructureType(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Aws;
    }

    protected function disk(InfrastructureConnection $connection, string $prefix): array
    {
        return $this->baseDisk($connection, $prefix);
    }
}

<?php

namespace App\Services\Admin\System\Storage;

use App\Enums\Admin\System\StorageDriverType;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\StorageDriverConfiguration;

class StorageRuntimeFingerprintService
{
    public function target(StorageDriverConfiguration $configuration): string
    {
        return $this->fingerprint(['id' => $configuration->id, 'driver' => $configuration->getRawOriginal('driver'), 'infrastructure_connection_id' => $configuration->infrastructure_connection_id, 'configuration' => $this->jsonValue($configuration->getRawOriginal('configuration')), 'is_enabled' => (bool) $configuration->getRawOriginal('is_enabled'), 'deleted_at' => $configuration->getRawOriginal('deleted_at')]);
    }

    public function infrastructure(InfrastructureConnection $connection): string
    {
        return $this->fingerprint(['id' => $connection->id, 'type' => $connection->getRawOriginal('type'), 'source' => $connection->getRawOriginal('source'), 'configuration' => $this->jsonValue($connection->getRawOriginal('configuration')), 'credentials' => $connection->getRawOriginal('credentials'), 'is_enabled' => (bool) $connection->getRawOriginal('is_enabled'), 'deleted_at' => $connection->getRawOriginal('deleted_at')]);
    }

    public function usesInfrastructure(StorageDriverConfiguration $configuration): bool
    {
        return $configuration->getRawOriginal('driver') !== StorageDriverType::Local->value;
    }

    private function fingerprint(array $state): string
    {
        return hash('sha256', json_encode($this->canonicalize($state), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return array_map(fn (mixed $item) => $this->canonicalize($item), $value);
    }

    private function jsonValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}

<?php

namespace App\Services\Admin\System\Infrastructure\Connections;

use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Models\Admin\System\InfrastructureConnection;
use InvalidArgumentException;

class RedisInfrastructureRuntimeConfigurationFactory
{
    public function make(InfrastructureConnection $connection): array
    {
        if ($connection->type !== InfrastructureConnectionType::Redis) {
            throw new InvalidArgumentException('Infrastructure connection must use the Redis type.');
        }

        if ($connection->source !== InfrastructureConnectionSource::Managed) {
            throw new InvalidArgumentException('Only managed Redis infrastructure connections have dynamic runtime configuration.');
        }

        $configuration = $connection->configuration ?? [];
        $credentials = $connection->secrets();

        return [
            'url' => null,
            'host' => $configuration['host'],
            'port' => (int) $configuration['port'],
            'database' => (int) $configuration['database'],
            'username' => ($configuration['username'] ?? '') !== '' ? $configuration['username'] : null,
            'password' => $credentials['password'] ?? null,
            'timeout' => (float) $configuration['connect_timeout_seconds'],
            'read_timeout' => (float) $configuration['connect_timeout_seconds'],
            'scheme' => ($configuration['tls'] ?? false) ? 'tls' : 'tcp',
        ];
    }
}

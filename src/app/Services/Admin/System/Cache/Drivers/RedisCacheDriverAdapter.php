<?php

namespace App\Services\Admin\System\Cache\Drivers;

use App\Contracts\Admin\System\Cache\CacheDriverAdapter;
use App\Data\Admin\System\Cache\CacheDriverDefinitionData;
use App\Data\Admin\System\Cache\CacheHealthResultData;
use App\Data\Admin\System\Cache\CacheRuntimeConfigurationData;
use App\Enums\Admin\System\CacheDriverType;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Cache\CacheStoreHealthProbe;
use App\Services\Admin\System\Infrastructure\Connections\RedisInfrastructureRuntimeConfigurationFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RedisCacheDriverAdapter implements CacheDriverAdapter
{
    public function __construct(private readonly RedisInfrastructureRuntimeConfigurationFactory $runtimeFactory, private readonly CacheStoreHealthProbe $probe) {}
    public function type(): CacheDriverType { return CacheDriverType::Redis; }
    public function definition(): CacheDriverDefinitionData { return new CacheDriverDefinitionData($this->type(), 'Redis', 'Use an enabled Redis Infrastructure Connection.', true, InfrastructureConnectionType::Redis->value, true); }
    public function validateAndNormalize(array $configuration): array { Validator::make($configuration, ['infrastructure_connection_id' => ['prohibited']])->validate(); return []; }
    public function runtimeConfiguration(CacheDriverConfiguration $configuration): CacheRuntimeConfigurationData
    {
        $this->validateAndNormalize($configuration->configuration ?? []); $connection = $this->resolve($configuration); $redisConnections = [];
        if ($connection->source === InfrastructureConnectionSource::Managed) { $name = 'simpledesk-infrastructure-'.$connection->id; $redisConnections[$name] = $this->runtimeFactory->make($connection); }
        else { $name = trim((string) ($connection->configuration['connection_name'] ?? '')); if ($name === '' || ! is_array(config("database.redis.{$name}"))) throw ValidationException::withMessages(['infrastructure_connection_id' => 'The deployment Redis connection no longer exists.']); }
        return new CacheRuntimeConfigurationData(['driver' => 'redis', 'connection' => $name, 'lock_connection' => $name], $redisConnections);
    }
    public function test(CacheDriverConfiguration $configuration): CacheHealthResultData { $runtime = $this->runtimeConfiguration($configuration); return $this->probe->test($runtime->store, $runtime->redisConnections, ['infrastructure_connection_id' => $configuration->infrastructure_connection_id]); }
    private function resolve(CacheDriverConfiguration $configuration): InfrastructureConnection
    {
        if (! $configuration->infrastructure_connection_id) throw ValidationException::withMessages(['infrastructure_connection_id' => 'Redis cache configuration requires an infrastructure connection.']);
        $connection = InfrastructureConnection::withTrashed()->find($configuration->infrastructure_connection_id);
        if (! $connection) throw ValidationException::withMessages(['infrastructure_connection_id' => 'The configured Redis infrastructure connection no longer exists.']);
        if ($connection->trashed()) throw ValidationException::withMessages(['infrastructure_connection_id' => 'The configured Redis infrastructure connection is archived.']);
        if ($connection->type !== InfrastructureConnectionType::Redis) throw ValidationException::withMessages(['infrastructure_connection_id' => 'The configured infrastructure connection is not Redis.']);
        if (! $connection->is_enabled) throw ValidationException::withMessages(['infrastructure_connection_id' => 'The configured Redis infrastructure connection is disabled.']); return $connection;
    }
}

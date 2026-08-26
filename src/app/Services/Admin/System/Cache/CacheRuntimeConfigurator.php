<?php

namespace App\Services\Admin\System\Cache;

use App\Enums\Admin\System\CacheConfigurationMode;
use App\Exceptions\Admin\System\Cache\InvalidManagedCacheConfigurationException;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\CacheDriverSettings;
use App\Services\Admin\System\Infrastructure\Connections\RedisInfrastructureRuntimeConnectionRegistrar;
use App\Services\Admin\System\Runtime\SystemRuntimeBootstrapPolicy;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CacheRuntimeConfigurator
{
    public function __construct(
        private readonly CacheDriverRegistry $registry,
        private readonly RedisInfrastructureRuntimeConnectionRegistrar $redisRegistrar,
        private readonly SystemRuntimeBootstrapPolicy $bootstrapPolicy,
    ) {}

    public function apply(): void
    {
        try {
            $tablesExist = Schema::hasTable('cache_driver_settings')
                && Schema::hasTable('cache_driver_configurations');
        } catch (Throwable $exception) {
            if ($this->bootstrapPolicy->maySkipDatabaseInspectionFailure()) {
                return;
            }

            throw new InvalidManagedCacheConfigurationException(
                'Unable to determine Cache ownership because its settings tables could not be inspected.',
                previous: $exception,
            );
        }

        if (! $tablesExist) {
            return;
        }

        $settings = CacheDriverSettings::query()->find(
            CacheDriverSettings::SINGLETON_ID,
        );

        if (! $settings || $settings->mode === CacheConfigurationMode::Deployment) {
            return;
        }

        if (! $settings->active_configuration_id) {
            throw new InvalidManagedCacheConfigurationException(
                'Managed cache mode requires an active configuration.',
            );
        }

        $configuration = CacheDriverConfiguration::withTrashed()->find(
            $settings->active_configuration_id,
        );

        if (! $configuration) {
            throw new InvalidManagedCacheConfigurationException(
                'The active managed cache configuration does not exist.',
            );
        }

        if ($configuration->trashed()) {
            throw new InvalidManagedCacheConfigurationException(
                'The active managed cache configuration is archived.',
            );
        }

        if (! $configuration->is_enabled) {
            throw new InvalidManagedCacheConfigurationException(
                'The active managed cache configuration is disabled.',
            );
        }

        try {
            $runtime = $this->registry
                ->adapter($configuration->driver)
                ->runtimeConfiguration($configuration);
        } catch (Throwable $exception) {
            throw new InvalidManagedCacheConfigurationException(
                'The active managed cache runtime configuration is invalid.',
                previous: $exception,
            );
        }

        $storeName = trim(
            (string) config(
                'simpledesk-cache.runtime.store_name',
                'simpledesk-managed',
            ),
        );

        if ($storeName === '') {
            throw new InvalidManagedCacheConfigurationException(
                'Managed cache store name cannot be empty.',
            );
        }

        if (config("cache.stores.{$storeName}") !== null) {
            throw new InvalidManagedCacheConfigurationException(
                "The managed cache store name [{$storeName}] collides with a deployment-defined store.",
            );
        }

        $this->redisRegistrar->registerMany(
            $runtime->redisConnections,
        );

        config()->set(
            "cache.stores.{$storeName}",
            $runtime->store,
        );

        config()->set(
            'cache.default',
            $storeName,
        );
    }
}

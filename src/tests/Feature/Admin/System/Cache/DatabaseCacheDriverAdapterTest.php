<?php

namespace Tests\Feature\Admin\System\Cache;

use App\Data\Admin\System\Cache\CacheHealthResultData;
use App\Enums\Admin\System\CacheDriverType;
use App\Enums\Admin\System\CacheHealthStatus;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Services\Admin\System\Cache\CacheStoreHealthProbe;
use App\Services\Admin\System\Cache\Drivers\DatabaseCacheDriverAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DatabaseCacheDriverAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_database_is_used_when_allowlist_is_empty(): void
    {
        $default = config(
            'database.default',
        );

        config()->set(
            'simpledesk-cache.database.allowed_connections',
            [],
        );

        $definition = $this->adapter()
            ->definition();

        $this->assertSame(
            [$default],
            $definition->options['database_connections'],
        );
    }

    public function test_explicit_allowlist_excludes_unknown_connections(): void
    {
        $default = config(
            'database.default',
        );

        config()->set(
            'simpledesk-cache.database.allowed_connections',
            [
                $default,
                'missing-database',
            ],
        );

        $definition = $this->adapter()
            ->definition();

        $this->assertSame(
            [$default],
            $definition->options['database_connections'],
        );
    }

    public function test_unknown_database_connection_is_rejected(): void
    {
        config()->set(
            'simpledesk-cache.database.allowed_connections',
            [],
        );

        $this->expectException(
            ValidationException::class,
        );

        $this->adapter()
            ->validateAndNormalize([
                'database_connection' => 'missing-database',
            ]);
    }

    public function test_runtime_configuration_uses_cache_and_lock_tables(): void
    {
        $default = config(
            'database.default',
        );

        config()->set(
            'simpledesk-cache.database.allowed_connections',
            [
                $default,
            ],
        );

        $configuration = $this->configuration(
            $default,
        );

        $runtime = $this->adapter()
            ->runtimeConfiguration(
                $configuration,
            );

        $this->assertSame(
            [
                'driver' => 'database',
                'connection' => $default,
                'table' => 'cache',
                'lock_connection' => $default,
                'lock_table' => 'cache_locks',
            ],
            $runtime->store,
        );
    }

    public function test_missing_cache_tables_are_reported_as_unhealthy(): void
    {
        config()->set(
            'database.connections.cache-empty',
            [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        );

        config()->set(
            'simpledesk-cache.database.allowed_connections',
            [
                'cache-empty',
            ],
        );

        $probe = $this->createMock(
            CacheStoreHealthProbe::class,
        );

        $probe
            ->expects($this->never())
            ->method('test');

        $adapter = new DatabaseCacheDriverAdapter(
            $probe,
        );

        $result = $adapter->test(
            $this->configuration(
                'cache-empty',
            ),
        );

        $this->assertSame(
            CacheHealthStatus::Unhealthy,
            $result->status,
        );

        $this->assertSame(
            'The configured database cache and lock tables are required.',
            $result->message,
        );
    }

    public function test_existing_cache_tables_delegate_to_semantic_health_probe(): void
    {
        $default = config(
            'database.default',
        );

        config()->set(
            'simpledesk-cache.database.allowed_connections',
            [
                $default,
            ],
        );

        $configuration = $this->configuration(
            $default,
        );

        $probe = $this->createMock(
            CacheStoreHealthProbe::class,
        );

        $probe
            ->expects($this->once())
            ->method('test')
            ->with(
                [
                    'driver' => 'database',
                    'connection' => $default,
                    'table' => 'cache',
                    'lock_connection' => $default,
                    'lock_table' => 'cache_locks',
                ],
                [],
                [
                    'database_connection' => $default,
                    'cache_table' => 'cache',
                    'lock_table' => 'cache_locks',
                ],
            )
            ->willReturn(
                new CacheHealthResultData(
                    status: CacheHealthStatus::Healthy,
                    latencyMs: 4,
                    message: 'Cache target verified.',
                ),
            );

        $adapter = new DatabaseCacheDriverAdapter(
            $probe,
        );

        $result = $adapter->test(
            $configuration,
        );

        $this->assertSame(
            CacheHealthStatus::Healthy,
            $result->status,
        );
    }

    private function adapter(): DatabaseCacheDriverAdapter
    {
        return new DatabaseCacheDriverAdapter(
            $this->createMock(
                CacheStoreHealthProbe::class,
            ),
        );
    }

    private function configuration(
        string $connection,
    ): CacheDriverConfiguration {
        return CacheDriverConfiguration::query()->create([
            'name' => 'Database Cache',
            'driver' => CacheDriverType::Database,
            'infrastructure_connection_id' => null,
            'configuration' => [
                'database_connection' => $connection,
            ],
            'is_enabled' => true,
        ]);
    }
}

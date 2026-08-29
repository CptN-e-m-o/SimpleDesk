<?php

namespace Tests\Feature\Admin\System\Cache;

use App\Enums\Admin\System\CacheConfigurationMode;
use App\Enums\Admin\System\CacheDriverType;
use App\Exceptions\Admin\System\Cache\ActiveCacheDriverConfigurationMutationException;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\CacheDriverSettings;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\User\User;
use App\Services\Admin\System\Cache\CacheDriverCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CacheDriverCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_configuration_is_created_with_normalized_configuration(): void
    {
        $actor = User::factory()->create();
        $connection = config('database.default');

        $configuration = $this->service()->create([
            'name' => 'Primary database cache',
            'driver' => CacheDriverType::Database->value,
            'infrastructure_connection_id' => null,
            'configuration' => [
                'database_connection' => $connection,
            ],
            'is_enabled' => true,
        ], $actor);

        $this->assertSame(
            CacheDriverType::Database,
            $configuration->driver,
        );

        $this->assertSame(
            $connection,
            $configuration->configuration['database_connection'],
        );

        $this->assertNull(
            $configuration->infrastructure_connection_id,
        );

        $this->assertTrue(
            $configuration->is_enabled,
        );

        $this->assertSame(
            $actor->id,
            $configuration->created_by,
        );

        $this->assertSame(
            $actor->id,
            $configuration->updated_by,
        );
    }

    public function test_redis_configuration_uses_top_level_infrastructure_foreign_key(): void
    {
        $actor = User::factory()->create();
        $infrastructure = InfrastructureConnection::factory()->create();

        $configuration = $this->service()->create([
            'name' => 'Primary Redis cache',
            'driver' => CacheDriverType::Redis->value,
            'infrastructure_connection_id' => $infrastructure->id,
            'configuration' => [],
            'is_enabled' => true,
        ], $actor);

        $this->assertSame(
            CacheDriverType::Redis,
            $configuration->driver,
        );

        $this->assertSame(
            $infrastructure->id,
            $configuration->infrastructure_connection_id,
        );

        $this->assertSame(
            [],
            $configuration->configuration,
        );
    }

    public function test_nested_infrastructure_connection_id_is_rejected_by_catalog_service(): void
    {
        $actor = User::factory()->create();

        try {
            $this->service()->create([
                'name' => 'Invalid Cache',
                'driver' => CacheDriverType::Database->value,
                'infrastructure_connection_id' => null,
                'configuration' => [
                    'infrastructure_connection_id' => 123,
                ],
                'is_enabled' => true,
            ], $actor);

            $this->fail(
                'Nested infrastructure_connection_id should be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'configuration.infrastructure_connection_id',
                $exception->errors(),
            );
        }

        $this->assertFalse(
            CacheDriverConfiguration::query()
                ->where('name', 'Invalid Cache')
                ->exists(),
        );
    }

    public function test_driver_cannot_be_changed_after_creation(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->fileConfiguration($actor);

        try {
            $this->service()->update(
                $configuration,
                [
                    'driver' => CacheDriverType::Database->value,
                ],
                $actor,
            );

            $this->fail(
                'Cache driver mutation should be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'driver',
                $exception->errors(),
            );
        }

        $this->assertSame(
            CacheDriverType::File,
            $configuration->fresh()->driver,
        );
    }

    public function test_partial_update_preserves_unspecified_values(): void
    {
        $actor = User::factory()->create();

        $configuration = CacheDriverConfiguration::query()->create([
            'name' => 'Original Cache',
            'driver' => CacheDriverType::Database,
            'infrastructure_connection_id' => null,
            'configuration' => [
                'database_connection' => config('database.default'),
            ],
            'is_enabled' => true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $updated = $this->service()->update(
            $configuration,
            [
                'name' => 'Renamed Cache',
            ],
            $actor,
        );

        $this->assertSame(
            'Renamed Cache',
            $updated->name,
        );

        $this->assertTrue(
            $updated->is_enabled,
        );

        $this->assertSame(
            config('database.default'),
            $updated->configuration['database_connection'],
        );
    }

    public function test_active_configuration_cannot_be_updated(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->fileConfiguration($actor);

        $this->activate(
            $configuration,
            $actor,
        );

        $this->expectException(
            ActiveCacheDriverConfigurationMutationException::class,
        );

        $this->service()->update(
            $configuration,
            [
                'name' => 'Mutated Active Cache',
            ],
            $actor,
        );
    }

    public function test_active_configuration_cannot_be_disabled(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->fileConfiguration($actor);

        $this->activate(
            $configuration,
            $actor,
        );

        $this->expectException(
            ActiveCacheDriverConfigurationMutationException::class,
        );

        $this->service()->setEnabled(
            $configuration,
            false,
            $actor,
        );
    }

    public function test_active_configuration_cannot_be_archived(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->fileConfiguration($actor);

        $this->activate(
            $configuration,
            $actor,
        );

        $this->expectException(
            ActiveCacheDriverConfigurationMutationException::class,
        );

        $this->service()->archive(
            $configuration,
            $actor,
        );
    }

    public function test_restored_configuration_remains_disabled(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->fileConfiguration($actor);

        $this->service()->archive(
            $configuration,
            $actor,
        );

        $restored = $this->service()->restore(
            $configuration->id,
            $actor,
        );

        $this->assertFalse(
            $restored->is_enabled,
        );

        $this->assertNull(
            $restored->deleted_at,
        );
    }

    public function test_enabling_configuration_does_not_activate_it(): void
    {
        $actor = User::factory()->create();

        $configuration = CacheDriverConfiguration::query()->create([
            'name' => 'Disabled Cache',
            'driver' => CacheDriverType::File,
            'infrastructure_connection_id' => null,
            'configuration' => [],
            'is_enabled' => false,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $updated = $this->service()->setEnabled(
            $configuration,
            true,
            $actor,
        );

        $this->assertTrue(
            $updated->is_enabled,
        );

        $this->assertFalse(
            CacheDriverSettings::query()
                ->where(
                    'mode',
                    CacheConfigurationMode::Managed->value,
                )
                ->where(
                    'active_configuration_id',
                    $configuration->id,
                )
                ->exists(),
        );
    }

    private function fileConfiguration(
        User $actor,
    ): CacheDriverConfiguration {
        return CacheDriverConfiguration::query()->create([
            'name' => 'File Cache',
            'driver' => CacheDriverType::File,
            'infrastructure_connection_id' => null,
            'configuration' => [],
            'is_enabled' => true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function activate(
        CacheDriverConfiguration $configuration,
        User $actor,
    ): void {
        CacheDriverSettings::query()->create([
            'id' => CacheDriverSettings::SINGLETON_ID,
            'mode' => CacheConfigurationMode::Managed,
            'active_configuration_id' => $configuration->id,
            'activated_at' => now(),
            'activated_by' => $actor->id,
        ]);
    }

    private function service(): CacheDriverCatalogService
    {
        return app(
            CacheDriverCatalogService::class,
        );
    }
}

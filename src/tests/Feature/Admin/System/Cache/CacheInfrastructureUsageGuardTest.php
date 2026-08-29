<?php

namespace Tests\Feature\Admin\System\Cache;

use App\Enums\Admin\System\CacheConfigurationMode;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\CacheDriverSettings;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\User\User;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CacheInfrastructureUsageGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_managed_cache_infrastructure_cannot_be_disabled(): void
    {
        $actor = User::factory()->create();
        $connection = InfrastructureConnection::factory()->create();

        $configuration = $this->activeConfiguration(
            $connection,
            $actor,
        );

        try {
            app(
                InfrastructureConnectionCatalogService::class,
            )->setEnabled(
                $connection,
                false,
                $actor,
            );

            $this->fail(
                'Active Cache infrastructure should not be disabled.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'is_enabled',
                $exception->errors(),
            );
        }

        $this->assertTrue(
            $connection->fresh()->is_enabled,
        );

        $this->assertSame(
            $configuration->id,
            CacheDriverSettings::query()
                ->findOrFail(
                    CacheDriverSettings::SINGLETON_ID,
                )
                ->active_configuration_id,
        );
    }

    public function test_active_managed_cache_infrastructure_cannot_be_archived(): void
    {
        $actor = User::factory()->create();
        $connection = InfrastructureConnection::factory()->create();

        $this->activeConfiguration(
            $connection,
            $actor,
        );

        try {
            app(
                InfrastructureConnectionCatalogService::class,
            )->archive(
                $connection,
                $actor,
            );

            $this->fail(
                'Active Cache infrastructure should not be archived.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'connection',
                $exception->errors(),
            );
        }

        $this->assertNull(
            $connection->fresh()->deleted_at,
        );
    }

    public function test_infrastructure_force_delete_is_blocked_by_archived_cache_profile(): void
    {
        $actor = User::factory()->create();
        $connection = InfrastructureConnection::factory()->create();

        $configuration = CacheDriverConfiguration::query()->create([
            'name' => 'Archived Redis Cache',
            'driver' => 'redis',
            'infrastructure_connection_id' => $connection->id,
            'configuration' => [],
            'is_enabled' => false,
        ]);

        $configuration->delete();
        $connection->delete();

        try {
            app(
                InfrastructureConnectionCatalogService::class,
            )->forceDelete(
                $connection->id,
                $actor,
            );

            $this->fail(
                'Infrastructure should not be permanently deleted while an archived Cache profile references it.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'connection',
                $exception->errors(),
            );
        }

        $this->assertNotNull(
            InfrastructureConnection::withTrashed()
                ->find($connection->id),
        );
    }

    public function test_inactive_cache_reference_does_not_prevent_disabling_infrastructure(): void
    {
        $actor = User::factory()->create();
        $connection = InfrastructureConnection::factory()->create();

        CacheDriverConfiguration::query()->create([
            'name' => 'Inactive Redis Cache',
            'driver' => 'redis',
            'infrastructure_connection_id' => $connection->id,
            'configuration' => [],
            'is_enabled' => true,
        ]);

        app(
            InfrastructureConnectionCatalogService::class,
        )->setEnabled(
            $connection,
            false,
            $actor,
        );

        $this->assertFalse(
            $connection->fresh()->is_enabled,
        );
    }

    private function activeConfiguration(
        InfrastructureConnection $connection,
        User $actor,
    ): CacheDriverConfiguration {
        $configuration = CacheDriverConfiguration::query()->create([
            'name' => 'Active Redis Cache',
            'driver' => 'redis',
            'infrastructure_connection_id' => $connection->id,
            'configuration' => [],
            'is_enabled' => true,
        ]);

        CacheDriverSettings::query()->create([
            'id' => CacheDriverSettings::SINGLETON_ID,
            'mode' => CacheConfigurationMode::Managed,
            'active_configuration_id' => $configuration->id,
            'activated_at' => now(),
            'activated_by' => $actor->id,
        ]);

        return $configuration;
    }
}

<?php

namespace Tests\Feature\Admin\System\Broadcasting;

use App\Enums\Admin\System\BroadcastConfigurationMode;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Models\Admin\System\BroadcastDriverConfiguration;
use App\Models\Admin\System\BroadcastDriverSettings;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\User\User;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BroadcastInfrastructureUsageGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_managed_broadcast_infrastructure_cannot_be_disabled(): void
    {
        $actor = User::factory()->create();
        $connection = $this->connection($actor);
        $configuration = $this->activeConfiguration($connection, $actor);

        try {
            $this->service()->setEnabled(
                $connection,
                false,
                $actor,
            );

            $this->fail('Active Broadcast infrastructure should not be disabled.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('is_enabled', $exception->errors());
        }

        $this->assertTrue($connection->fresh()->is_enabled);

        $this->assertSame(
            $configuration->id,
            BroadcastDriverSettings::query()
                ->findOrFail(BroadcastDriverSettings::SINGLETON_ID)
                ->active_configuration_id,
        );
    }

    public function test_active_managed_broadcast_infrastructure_cannot_be_archived(): void
    {
        $actor = User::factory()->create();
        $connection = $this->connection($actor);

        $this->activeConfiguration($connection, $actor);

        try {
            $this->service()->archive(
                $connection,
                $actor,
            );

            $this->fail('Active Broadcast infrastructure should not be archived.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('connection', $exception->errors());
        }

        $this->assertNull($connection->fresh()->deleted_at);
    }

    public function test_active_managed_broadcast_runtime_configuration_cannot_be_changed(): void
    {
        $actor = User::factory()->create();
        $connection = $this->connection($actor);

        $this->activeConfiguration($connection, $actor);

        $configuration = $connection->configuration;
        $configuration['host'] = 'different-reverb-host';

        try {
            $this->service()->update(
                $connection,
                [
                    'name' => $connection->name,
                    'configuration' => $configuration,
                ],
                $actor,
            );

            $this->fail('Active Broadcast infrastructure runtime settings should not be changed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('configuration', $exception->errors());
        }

        $this->assertSame(
            '127.0.0.1',
            $connection->fresh()->configuration['host'],
        );
    }

    public function test_active_managed_broadcast_credentials_cannot_be_rotated(): void
    {
        $actor = User::factory()->create();
        $connection = $this->connection($actor);

        $this->activeConfiguration($connection, $actor);

        try {
            $this->service()->update(
                $connection,
                [
                    'name' => $connection->name,
                    'credentials' => [
                        'app_secret' => 'new-secret',
                    ],
                ],
                $actor,
            );

            $this->fail('Active Broadcast infrastructure credentials should not be changed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('credentials', $exception->errors());
        }

        $this->assertSame(
            'simpledesk-test-secret',
            $connection->fresh()->secrets()['app_secret'],
        );
    }

    public function test_active_managed_broadcast_infrastructure_can_be_renamed(): void
    {
        $actor = User::factory()->create();
        $connection = $this->connection($actor);

        $this->activeConfiguration($connection, $actor);

        $updated = $this->service()->update(
            $connection,
            [
                'name' => 'Renamed Reverb',
            ],
            $actor,
        );

        $this->assertSame('Renamed Reverb', $updated->name);
        $this->assertSame('127.0.0.1', $updated->configuration['host']);
        $this->assertSame(
            'simpledesk-test-secret',
            $updated->secrets()['app_secret'],
        );
    }

    public function test_infrastructure_force_delete_is_blocked_by_archived_broadcast_profile(): void
    {
        $actor = User::factory()->create();
        $connection = $this->connection($actor);

        $configuration = $this->configuration(
            $connection,
            $actor,
            false,
        );

        $configuration->delete();
        $connection->delete();

        try {
            $this->service()->forceDelete(
                $connection->id,
                $actor,
            );

            $this->fail(
                'Infrastructure should not be permanently deleted while an archived Broadcast profile references it.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('connection', $exception->errors());
        }

        $this->assertNotNull(
            InfrastructureConnection::withTrashed()
                ->find($connection->id),
        );
    }

    public function test_inactive_broadcast_reference_does_not_prevent_disabling_infrastructure(): void
    {
        $actor = User::factory()->create();
        $connection = $this->connection($actor);

        $this->configuration(
            $connection,
            $actor,
            true,
        );

        $this->service()->setEnabled(
            $connection,
            false,
            $actor,
        );

        $this->assertFalse($connection->fresh()->is_enabled);
    }

    private function connection(User $actor): InfrastructureConnection
    {
        return InfrastructureConnection::query()->create([
            'name' => 'Test Reverb',
            'type' => InfrastructureConnectionType::Reverb,
            'source' => InfrastructureConnectionSource::Managed,
            'configuration' => [
                'app_id' => 'simpledesk-test',
                'host' => '127.0.0.1',
                'port' => 8080,
                'scheme' => 'http',
                'cluster' => '',
                'public_host' => '',
                'public_port' => null,
                'public_scheme' => '',
            ],
            'credentials' => [
                'app_key' => 'simpledesk-test-key',
                'app_secret' => 'simpledesk-test-secret',
            ],
            'is_enabled' => true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function configuration(
        InfrastructureConnection $connection,
        User $actor,
        bool $enabled,
    ): BroadcastDriverConfiguration {
        return BroadcastDriverConfiguration::query()->create([
            'name' => 'Reverb Broadcast',
            'driver' => 'reverb',
            'infrastructure_connection_id' => $connection->id,
            'configuration' => [],
            'is_enabled' => $enabled,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function activeConfiguration(
        InfrastructureConnection $connection,
        User $actor,
    ): BroadcastDriverConfiguration {
        $configuration = $this->configuration(
            $connection,
            $actor,
            true,
        );

        BroadcastDriverSettings::query()->create([
            'id' => BroadcastDriverSettings::SINGLETON_ID,
            'mode' => BroadcastConfigurationMode::Managed,
            'active_configuration_id' => $configuration->id,
            'activated_at' => now(),
            'activated_by' => $actor->id,
        ]);

        return $configuration;
    }

    private function service(): InfrastructureConnectionCatalogService
    {
        return app(InfrastructureConnectionCatalogService::class);
    }
}

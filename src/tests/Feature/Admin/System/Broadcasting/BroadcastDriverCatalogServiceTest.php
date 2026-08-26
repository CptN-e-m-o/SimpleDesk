<?php

namespace Tests\Feature\Admin\System\Broadcasting;

use App\Enums\Admin\System\BroadcastConfigurationMode;
use App\Enums\Admin\System\BroadcastDriverType;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Models\Admin\System\BroadcastDriverConfiguration;
use App\Models\Admin\System\BroadcastDriverSettings;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\User\User;
use App\Services\Admin\System\Broadcasting\BroadcastDriverCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BroadcastDriverCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reverb_configuration_is_created_with_top_level_infrastructure_connection(): void
    {
        $actor = User::factory()->create();
        $infrastructure = $this->infrastructureConnection($actor, InfrastructureConnectionType::Reverb);

        $configuration = $this->service()->create([
            'name' => 'Primary Reverb',
            'driver' => BroadcastDriverType::Reverb->value,
            'infrastructure_connection_id' => $infrastructure->id,
            'configuration' => [],
            'is_enabled' => true,
        ], $actor);

        $this->assertSame(BroadcastDriverType::Reverb, $configuration->driver);
        $this->assertSame($infrastructure->id, $configuration->infrastructure_connection_id);
        $this->assertSame([], $configuration->configuration);
        $this->assertTrue($configuration->is_enabled);
        $this->assertSame($actor->id, $configuration->created_by);
        $this->assertSame($actor->id, $configuration->updated_by);
    }

    public function test_pusher_configuration_is_created_with_matching_infrastructure_connection(): void
    {
        $actor = User::factory()->create();
        $infrastructure = $this->infrastructureConnection($actor, InfrastructureConnectionType::Pusher);

        $configuration = $this->service()->create([
            'name' => 'Primary Pusher',
            'driver' => BroadcastDriverType::Pusher->value,
            'infrastructure_connection_id' => $infrastructure->id,
            'configuration' => [],
            'is_enabled' => true,
        ], $actor);

        $this->assertSame(BroadcastDriverType::Pusher, $configuration->driver);
        $this->assertSame($infrastructure->id, $configuration->infrastructure_connection_id);
        $this->assertSame([], $configuration->configuration);
    }

    public function test_provider_settings_cannot_be_stored_inside_broadcast_profile(): void
    {
        $actor = User::factory()->create();
        $infrastructure = $this->infrastructureConnection($actor, InfrastructureConnectionType::Reverb);

        try {
            $this->service()->create([
                'name' => 'Invalid Reverb',
                'driver' => BroadcastDriverType::Reverb->value,
                'infrastructure_connection_id' => $infrastructure->id,
                'configuration' => [
                    'host' => 'reverb.internal',
                ],
                'is_enabled' => true,
            ], $actor);

            $this->fail('Provider connection settings should not be stored in a Broadcast profile.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('configuration', $exception->errors());
        }

        $this->assertDatabaseMissing('broadcast_driver_configurations', [
            'name' => 'Invalid Reverb',
        ]);
    }

    public function test_infrastructure_connection_type_must_match_broadcast_driver(): void
    {
        $actor = User::factory()->create();
        $redis = InfrastructureConnection::factory()->create();

        try {
            $this->service()->create([
                'name' => 'Invalid Reverb',
                'driver' => BroadcastDriverType::Reverb->value,
                'infrastructure_connection_id' => $redis->id,
                'configuration' => [],
                'is_enabled' => true,
            ], $actor);

            $this->fail('A Redis infrastructure connection should not be accepted for Reverb.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('infrastructure_connection_id', $exception->errors());
        }
    }

    public function test_disabled_infrastructure_connection_cannot_be_used(): void
    {
        $actor = User::factory()->create();
        $infrastructure = $this->infrastructureConnection(
            $actor,
            InfrastructureConnectionType::Reverb,
            false,
        );

        try {
            $this->service()->create([
                'name' => 'Disabled Reverb',
                'driver' => BroadcastDriverType::Reverb->value,
                'infrastructure_connection_id' => $infrastructure->id,
                'configuration' => [],
                'is_enabled' => true,
            ], $actor);

            $this->fail('A disabled infrastructure connection should not be accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('infrastructure_connection_id', $exception->errors());
        }
    }

    public function test_archived_infrastructure_connection_cannot_be_used(): void
    {
        $actor = User::factory()->create();
        $infrastructure = $this->infrastructureConnection($actor, InfrastructureConnectionType::Reverb);
        $infrastructure->delete();

        try {
            $this->service()->create([
                'name' => 'Archived Reverb',
                'driver' => BroadcastDriverType::Reverb->value,
                'infrastructure_connection_id' => $infrastructure->id,
                'configuration' => [],
                'is_enabled' => true,
            ], $actor);

            $this->fail('An archived infrastructure connection should not be accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('infrastructure_connection_id', $exception->errors());
        }
    }

    public function test_driver_cannot_be_changed_after_creation(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->profile($actor, BroadcastDriverType::Reverb);

        try {
            $this->service()->update($configuration, [
                'name' => $configuration->name,
                'driver' => BroadcastDriverType::Pusher->value,
                'configuration' => [],
                'is_enabled' => true,
            ], $actor);

            $this->fail('Broadcast driver mutation should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('driver', $exception->errors());
        }

        $this->assertSame(BroadcastDriverType::Reverb, $configuration->fresh()->driver);
    }

    public function test_active_configuration_cannot_be_updated(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->profile($actor, BroadcastDriverType::Reverb);
        $this->activate($configuration, $actor);

        $this->expectException(ValidationException::class);

        $this->service()->update($configuration, [
            'name' => 'Mutated Active Reverb',
            'configuration' => [],
            'is_enabled' => true,
        ], $actor);
    }

    public function test_active_configuration_cannot_be_disabled(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->profile($actor, BroadcastDriverType::Reverb);
        $this->activate($configuration, $actor);

        $this->expectException(ValidationException::class);

        $this->service()->setEnabled($configuration, false, $actor);
    }

    public function test_active_configuration_cannot_be_archived(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->profile($actor, BroadcastDriverType::Reverb);
        $this->activate($configuration, $actor);

        $this->expectException(ValidationException::class);

        $this->service()->archive($configuration, $actor);
    }

    public function test_restored_configuration_remains_disabled(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->profile($actor, BroadcastDriverType::Reverb);

        $this->service()->archive($configuration, $actor);

        $restored = $this->service()->restore($configuration->id, $actor);

        $this->assertFalse($restored->is_enabled);
        $this->assertNull($restored->deleted_at);
    }

    public function test_configuration_cannot_be_restored_when_infrastructure_is_disabled(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->profile($actor, BroadcastDriverType::Reverb);

        $this->service()->archive($configuration, $actor);

        $configuration->infrastructureConnection->update([
            'is_enabled' => false,
        ]);

        try {
            $this->service()->restore($configuration->id, $actor);

            $this->fail('A profile with unavailable infrastructure should not be restored.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('configuration', $exception->errors());
        }

        $this->assertNotNull(
            BroadcastDriverConfiguration::withTrashed()
                ->findOrFail($configuration->id)
                ->deleted_at,
        );
    }

    public function test_enabling_configuration_does_not_activate_it(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->profile($actor, BroadcastDriverType::Reverb);

        $configuration->update([
            'is_enabled' => false,
        ]);

        $updated = $this->service()->setEnabled(
            $configuration->refresh(),
            true,
            $actor,
        );

        $this->assertTrue($updated->is_enabled);

        $this->assertFalse(
            BroadcastDriverSettings::query()
                ->where('mode', BroadcastConfigurationMode::Managed->value)
                ->where('active_configuration_id', $configuration->id)
                ->exists(),
        );
    }

    public function test_ably_configuration_is_rejected(): void
    {
        $actor = User::factory()->create();

        try {
            $this->service()->create([
                'name' => 'Ably',
                'driver' => BroadcastDriverType::Ably->value,
                'infrastructure_connection_id' => null,
                'configuration' => [],
                'is_enabled' => true,
            ], $actor);

            $this->fail('Ably should remain unavailable.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('driver', $exception->errors());
        }

        $this->assertDatabaseMissing('broadcast_driver_configurations', [
            'name' => 'Ably',
        ]);
    }

    private function profile(
        User $actor,
        BroadcastDriverType $type,
    ): BroadcastDriverConfiguration {
        $infrastructure = $this->infrastructureConnection(
            $actor,
            match ($type) {
                BroadcastDriverType::Reverb => InfrastructureConnectionType::Reverb,
                BroadcastDriverType::Pusher => InfrastructureConnectionType::Pusher,
                default => throw new \LogicException('Unsupported test driver.'),
            },
        );

        return $this->service()->create([
            'name' => $type === BroadcastDriverType::Reverb ? 'Reverb' : 'Pusher',
            'driver' => $type->value,
            'infrastructure_connection_id' => $infrastructure->id,
            'configuration' => [],
            'is_enabled' => true,
        ], $actor);
    }

    private function infrastructureConnection(
        User $actor,
        InfrastructureConnectionType $type,
        bool $enabled = true,
    ): InfrastructureConnection {
        $configuration = match ($type) {
            InfrastructureConnectionType::Reverb => [
                'app_id' => 'simpledesk-test',
                'host' => '127.0.0.1',
                'port' => 8080,
                'scheme' => 'http',
                'cluster' => '',
                'public_host' => '',
                'public_port' => null,
                'public_scheme' => '',
            ],
            InfrastructureConnectionType::Pusher => [
                'app_id' => 'simpledesk-test',
                'host' => '',
                'port' => 443,
                'scheme' => 'https',
                'cluster' => 'eu',
                'public_host' => '',
                'public_port' => null,
                'public_scheme' => '',
            ],
            default => throw new \LogicException('Unsupported infrastructure type.'),
        };

        return InfrastructureConnection::query()->create([
            'name' => $type === InfrastructureConnectionType::Reverb ? 'Test Reverb' : 'Test Pusher',
            'type' => $type,
            'source' => InfrastructureConnectionSource::Managed,
            'configuration' => $configuration,
            'credentials' => [
                'app_key' => 'simpledesk-test-key',
                'app_secret' => 'simpledesk-test-secret',
            ],
            'is_enabled' => $enabled,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function activate(
        BroadcastDriverConfiguration $configuration,
        User $actor,
    ): void {
        BroadcastDriverSettings::query()->create([
            'id' => BroadcastDriverSettings::SINGLETON_ID,
            'mode' => BroadcastConfigurationMode::Managed,
            'active_configuration_id' => $configuration->id,
            'activated_at' => now(),
            'activated_by' => $actor->id,
        ]);
    }

    private function service(): BroadcastDriverCatalogService
    {
        return app(BroadcastDriverCatalogService::class);
    }
}

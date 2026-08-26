<?php

namespace Tests\Feature\Admin\System\Broadcasting;

use App\Enums\Admin\System\BroadcastConfigurationMode;
use App\Exceptions\Admin\System\Broadcasting\InvalidManagedBroadcastConfigurationException;
use App\Models\Admin\System\BroadcastDriverConfiguration;
use App\Models\Admin\System\BroadcastDriverSettings;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\User\User;
use App\Services\Admin\System\Broadcasting\BroadcastRuntimeConfigurator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastRuntimeConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_settings_and_deployment_mode_leave_default_untouched(): void
    {
        config()->set('broadcasting.default', 'log');
        app(BroadcastRuntimeConfigurator::class)->apply();
        $this->assertSame('log', config('broadcasting.default'));
        BroadcastDriverSettings::query()->create(['id' => 1, 'mode' => BroadcastConfigurationMode::Deployment]);
        app(BroadcastRuntimeConfigurator::class)->apply();
        $this->assertSame('log', config('broadcasting.default'));
    }

    public function test_managed_profile_installs_only_the_synthetic_connection(): void
    {
        config()->offsetUnset('broadcasting.connections.simpledesk-managed');
        $configuration = $this->configuration();
        BroadcastDriverSettings::query()->create(['id' => 1, 'mode' => BroadcastConfigurationMode::Managed, 'active_configuration_id' => $configuration->id]);
        app(BroadcastRuntimeConfigurator::class)->apply();
        $this->assertSame('simpledesk-managed', config('broadcasting.default'));
        $this->assertSame('pusher', config('broadcasting.connections.simpledesk-managed.driver'));
        $this->assertSame('deployment-log', config('broadcasting.connections.deployment-log.driver'));
    }

    public function test_collision_and_corrupt_managed_state_fail_without_fallback(): void
    {
        config()->set('broadcasting.default', 'log');
        config()->set('broadcasting.connections.simpledesk-managed', ['driver' => 'log']);
        $configuration = $this->configuration();
        BroadcastDriverSettings::query()->create(['id' => 1, 'mode' => 'managed', 'active_configuration_id' => $configuration->id]);
        $this->expectException(InvalidManagedBroadcastConfigurationException::class);
        app(BroadcastRuntimeConfigurator::class)->apply();
        $this->assertSame('log', config('broadcasting.default'));
    }

    private function configuration(): BroadcastDriverConfiguration
    {
        config()->set('broadcasting.connections.deployment-log', ['driver' => 'deployment-log']);
        $user = User::factory()->create();
        $connection = InfrastructureConnection::query()->create(['name' => 'Reverb', 'type' => 'reverb', 'source' => 'managed', 'configuration' => ['app_id' => 'app', 'host' => 'reverb.internal', 'port' => 8080, 'scheme' => 'http', 'cluster' => '', 'public_host' => 'realtime.example.test', 'public_port' => 443, 'public_scheme' => 'https'], 'credentials' => ['app_key' => 'public-key', 'app_secret' => 'secret'], 'is_enabled' => true, 'created_by' => $user->id, 'updated_by' => $user->id]);

        return BroadcastDriverConfiguration::query()->create(['name' => 'Managed Reverb', 'driver' => 'reverb', 'infrastructure_connection_id' => $connection->id, 'configuration' => [], 'is_enabled' => true, 'created_by' => $user->id, 'updated_by' => $user->id]);
    }
}

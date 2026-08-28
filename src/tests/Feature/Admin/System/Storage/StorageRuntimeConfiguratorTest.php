<?php

namespace Tests\Feature\Admin\System\Storage;

use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Exceptions\Admin\System\Storage\InvalidManagedStorageConfigurationException;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\StorageDriverConfiguration;
use App\Models\Admin\System\StorageDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Storage\StorageRuntimeConfigurator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageRuntimeConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_deployment_mode_leaves_filesystem_untouched(): void
    {
        config()->set('filesystems.default', 'deployment-disk');
        StorageDriverSettings::query()->create(['id' => 1, 'mode' => 'deployment']);
        $this->app->make(StorageRuntimeConfigurator::class)->apply();
        $this->assertSame('deployment-disk', config('filesystems.default'));
    }

    public function test_managed_local_creates_private_synthetic_disk(): void
    {
        $profile = $this->profile('local', null, []);
        StorageDriverSettings::query()->create(['id' => 1, 'mode' => 'managed', 'active_configuration_id' => $profile->id]);
        $this->app->make(StorageRuntimeConfigurator::class)->apply();
        $this->assertSame('simpledesk-managed', config('filesystems.default'));
        $this->assertSame(storage_path('app/private'), config('filesystems.disks.simpledesk-managed.root'));
    }

    public function test_managed_s3_uses_connection_and_prefix(): void
    {
        $actor = User::factory()->create();
        $connection = InfrastructureConnection::query()->create(['name' => 'AWS', 'type' => InfrastructureConnectionType::Aws, 'source' => InfrastructureConnectionSource::Managed, 'configuration' => ['region' => 'eu-west-1', 'bucket' => 'bucket'], 'credentials' => ['access_key_id' => 'key', 'secret_access_key' => 'secret'], 'is_enabled' => true, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
        $profile = $this->profile('s3', $connection->id, ['prefix' => 'simpledesk']);
        StorageDriverSettings::query()->create(['id' => 1, 'mode' => 'managed', 'active_configuration_id' => $profile->id]);
        $this->app->make(StorageRuntimeConfigurator::class)->apply();
        $this->assertSame('s3', config('filesystems.disks.simpledesk-managed.driver'));
        $this->assertSame('bucket', config('filesystems.disks.simpledesk-managed.bucket'));
        $this->assertSame('simpledesk', config('filesystems.disks.simpledesk-managed.root'));
    }

    public function test_corrupt_managed_state_fails_explicitly(): void
    {
        StorageDriverSettings::query()->create(['id' => 1, 'mode' => 'managed']);
        $this->expectException(InvalidManagedStorageConfigurationException::class);
        $this->app->make(StorageRuntimeConfigurator::class)->apply();
    }

    private function profile(string $driver, ?int $connection, array $configuration): StorageDriverConfiguration
    {
        $actor = User::factory()->create();

        return StorageDriverConfiguration::query()->create(['name' => 'Target', 'driver' => $driver, 'infrastructure_connection_id' => $connection, 'configuration' => $configuration, 'is_enabled' => true, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
    }
}

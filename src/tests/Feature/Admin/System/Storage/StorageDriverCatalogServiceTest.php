<?php

namespace Tests\Feature\Admin\System\Storage;

use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\StorageDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Storage\StorageDriverCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StorageDriverCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_profile_lifecycle_and_driver_immutability(): void
    {
        $actor = User::factory()->create();
        $service = $this->app->make(StorageDriverCatalogService::class);
        $profile = $service->create(['name' => 'Private local', 'driver' => 'local', 'configuration' => [], 'is_enabled' => true], $actor);
        $this->assertNull($profile->infrastructure_connection_id);

        try {
            $service->update($profile, ['name' => 'Changed', 'driver' => 's3', 'configuration' => [], 'is_enabled' => true], $actor);
            $this->fail('Expected immutable driver validation.');
        } catch (ValidationException) {
            $this->assertSame('local', $profile->getRawOriginal('driver'));
        }

        $service->archive($profile, $actor);
        $restored = $service->restore($profile->id, $actor);
        $this->assertFalse($restored->is_enabled);
    }

    public function test_external_profiles_require_matching_managed_infrastructure(): void
    {
        $actor = User::factory()->create();
        $connection = InfrastructureConnection::query()->create(['name' => 'AWS', 'type' => InfrastructureConnectionType::Aws, 'source' => InfrastructureConnectionSource::Managed, 'configuration' => ['region' => 'us-east-1', 'bucket' => 'private'], 'credentials' => ['access_key_id' => 'id', 'secret_access_key' => 'secret'], 'is_enabled' => true, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
        $profile = $this->app->make(StorageDriverCatalogService::class)->create(['name' => 'S3', 'driver' => 's3', 'infrastructure_connection_id' => $connection->id, 'configuration' => ['prefix' => '/simpledesk//objects/'], 'is_enabled' => true], $actor);

        $this->assertSame(['prefix' => 'simpledesk/objects'], $profile->configuration);
        $this->assertSame($connection->id, $profile->infrastructure_connection_id);
    }

    public function test_active_profile_cannot_be_mutated(): void
    {
        $actor = User::factory()->create();
        $service = $this->app->make(StorageDriverCatalogService::class);
        $profile = $service->create(['name' => 'Private local', 'driver' => 'local', 'configuration' => [], 'is_enabled' => true], $actor);
        StorageDriverSettings::query()->create(['id' => 1, 'mode' => 'managed', 'active_configuration_id' => $profile->id]);

        $this->expectException(ValidationException::class);
        $service->setEnabled($profile, false, $actor);
    }
}

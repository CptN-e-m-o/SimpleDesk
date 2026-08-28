<?php

namespace Tests\Feature\Admin\System\Storage;

use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\StorageDriverConfiguration;
use App\Models\Admin\System\StorageDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InfrastructureConnectionStorageUsageGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_managed_storage_reference_blocks_disable(): void
    {
        [$actor, $connection, $profile] = $this->state();
        StorageDriverSettings::query()->create(['id' => 1, 'mode' => 'managed', 'active_configuration_id' => $profile->id]);
        $this->expectException(ValidationException::class);
        app(InfrastructureConnectionCatalogService::class)->setEnabled($connection, false, $actor);
    }

    public function test_archived_storage_reference_blocks_permanent_connection_delete(): void
    {
        [$actor, $connection, $profile] = $this->state();
        $profile->delete();
        $connection->delete();
        $this->expectException(ValidationException::class);
        app(InfrastructureConnectionCatalogService::class)->forceDelete($connection->id, $actor);
    }

    private function state(): array
    {
        $actor = User::factory()->create();
        $connection = InfrastructureConnection::query()->create(['name' => 'AWS', 'type' => 'aws', 'source' => 'managed', 'configuration' => ['region' => 'us-east-1', 'bucket' => 'bucket'], 'credentials' => ['access_key_id' => 'id', 'secret_access_key' => 'secret'], 'is_enabled' => true, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
        $profile = StorageDriverConfiguration::query()->create(['name' => 'S3', 'driver' => 's3', 'infrastructure_connection_id' => $connection->id, 'configuration' => [], 'is_enabled' => true, 'created_by' => $actor->id, 'updated_by' => $actor->id]);

        return [$actor, $connection, $profile];
    }
}

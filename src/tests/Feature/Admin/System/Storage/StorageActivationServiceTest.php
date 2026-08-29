<?php

namespace Tests\Feature\Admin\System\Storage;

use App\Enums\Admin\System\StorageDriverType;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\StorageDriverConfiguration;
use App\Models\Admin\System\StorageDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Queues\QueueWorkerRestartService;
use App\Services\Admin\System\Storage\StorageActivationService;
use App\Services\Admin\System\Storage\StorageDriverRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\Fakes\Admin\System\FakeStorageDriverAdapter;
use Tests\TestCase;

class StorageActivationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_healthy_activation_commits_before_restart_signal(): void
    {
        [$actor, $profile] = $this->localState();
        $restart = Mockery::mock(QueueWorkerRestartService::class);
        $restart->shouldReceive('signal')->once()->andReturnUsing(function () use ($profile) {
            $this->assertSame($profile->id, StorageDriverSettings::query()->findOrFail(1)->active_configuration_id);
        });
        app()->instance(QueueWorkerRestartService::class, $restart);
        $result = app(StorageActivationService::class)->activate($profile, $actor);
        $this->assertTrue($result->restartSignaled);
    }

    public function test_profile_change_after_preflight_is_rejected_even_when_forced(): void
    {
        [$actor, $profile, $adapter] = $this->localState();
        $adapter->onTest = fn () => $profile->update(['is_enabled' => false]);
        $this->expectException(ValidationException::class);
        app(StorageActivationService::class)->activate($profile, $actor, true);
    }

    public function test_infrastructure_credential_change_after_preflight_is_rejected(): void
    {
        [$actor, $profile, $adapter, $connection] = $this->externalState();
        $adapter->onTest = fn () => $connection->update(['credentials' => ['access_key_id' => 'changed', 'secret_access_key' => 'changed']]);
        $this->expectException(ValidationException::class);
        app(StorageActivationService::class)->activate($profile, $actor);
    }

    public function test_restart_failure_keeps_committed_ownership(): void
    {
        [$actor, $profile] = $this->localState();
        $restart = Mockery::mock(QueueWorkerRestartService::class);
        $restart->shouldReceive('signal')->andThrow(new \RuntimeException('failed'));
        app()->instance(QueueWorkerRestartService::class, $restart);
        $result = app(StorageActivationService::class)->activate($profile, $actor);
        $this->assertFalse($result->restartSignaled);
        $this->assertSame($profile->id, StorageDriverSettings::query()->findOrFail(1)->active_configuration_id);
    }

    private function localState(): array
    {
        $adapter = new FakeStorageDriverAdapter;
        app()->instance(FakeStorageDriverAdapter::class, $adapter);
        app()->instance(StorageDriverRegistry::class, new StorageDriverRegistry(app(), ['local' => FakeStorageDriverAdapter::class]));
        app()->instance(QueueWorkerRestartService::class, Mockery::mock(QueueWorkerRestartService::class));
        $actor = User::factory()->create();
        $profile = StorageDriverConfiguration::query()->create(['name' => 'Local', 'driver' => 'local', 'configuration' => [], 'is_enabled' => true, 'created_by' => $actor->id, 'updated_by' => $actor->id]);

        return [$actor, $profile, $adapter];
    }

    private function externalState(): array
    {
        $adapter = new FakeStorageDriverAdapter(StorageDriverType::S3);
        app()->instance(FakeStorageDriverAdapter::class, $adapter);
        app()->instance(StorageDriverRegistry::class, new StorageDriverRegistry(app(), ['s3' => FakeStorageDriverAdapter::class]));
        app()->instance(QueueWorkerRestartService::class, Mockery::mock(QueueWorkerRestartService::class));
        $actor = User::factory()->create();
        $connection = InfrastructureConnection::query()->create(['name' => 'AWS', 'type' => 'aws', 'source' => 'managed', 'configuration' => ['region' => 'us-east-1', 'bucket' => 'bucket'], 'credentials' => ['access_key_id' => 'id', 'secret_access_key' => 'secret'], 'is_enabled' => true, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
        $profile = StorageDriverConfiguration::query()->create(['name' => 'S3', 'driver' => 's3', 'infrastructure_connection_id' => $connection->id, 'configuration' => [], 'is_enabled' => true, 'created_by' => $actor->id, 'updated_by' => $actor->id]);

        return [$actor, $profile, $adapter, $connection];
    }
}

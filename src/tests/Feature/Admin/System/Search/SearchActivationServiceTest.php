<?php

namespace Tests\Feature\Admin\System\Search;

use App\Enums\Admin\System\SearchDriverType;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\SearchDriverConfiguration;
use App\Models\User\User;
use App\Services\Admin\System\Queues\QueueWorkerRestartService;
use App\Services\Admin\System\Search\SearchActivationService;
use App\Services\Admin\System\Search\SearchDriverRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\Fakes\Admin\System\FakeSearchDriverAdapter;
use Tests\TestCase;

class SearchActivationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_healthy_managed_activation_commits_and_signals_restart(): void
    {
        app()->instance(SearchDriverRegistry::class, new SearchDriverRegistry(app(), ['database' => FakeSearchDriverAdapter::class]));
        $restart = Mockery::mock(QueueWorkerRestartService::class);
        $restart->shouldReceive('signal')->once();
        app()->instance(QueueWorkerRestartService::class, $restart);
        $actor = User::factory()->create();
        $profile = SearchDriverConfiguration::query()->create(['name' => 'Database', 'driver' => 'database', 'configuration' => [], 'is_enabled' => true, 'created_by' => $actor->id]);
        $result = app(SearchActivationService::class)->activate($profile, $actor);
        $this->assertSame('managed', $result->settings->mode->value);
        $this->assertSame($profile->id, $result->settings->active_configuration_id);
        $this->assertTrue($result->restartSignaled);
    }

    public function test_target_changed_after_preflight_is_rejected(): void
    {
        [$actor, $profile, $adapter] = $this->databaseState();
        $adapter->onTest = fn () => $profile->update(['configuration' => ['changed' => true]]);
        $this->expectException(ValidationException::class);
        app(SearchActivationService::class)->activate($profile, $actor);
    }

    public function test_force_activation_does_not_bypass_changed_target(): void
    {
        [$actor, $profile, $adapter] = $this->databaseState();
        $adapter->onTest = fn () => $profile->update(['is_enabled' => false]);
        $this->expectException(ValidationException::class);
        app(SearchActivationService::class)->activate($profile, $actor, true);
    }

    public function test_infrastructure_configuration_changed_after_preflight_is_rejected(): void
    {
        [$actor, $profile, $adapter, $connection] = $this->externalState();
        $adapter->onTest = fn () => $connection->update(['configuration' => ['host' => 'https://changed.test']]);
        $this->expectException(ValidationException::class);
        app(SearchActivationService::class)->activate($profile, $actor);
    }

    public function test_infrastructure_credentials_changed_after_preflight_are_rejected(): void
    {
        [$actor, $profile, $adapter, $connection] = $this->externalState();
        $adapter->onTest = fn () => $connection->update(['credentials' => ['api_key' => 'changed-secret']]);
        $this->expectException(ValidationException::class);
        app(SearchActivationService::class)->activate($profile, $actor);
    }

    private function databaseState(): array
    {
        $adapter = new FakeSearchDriverAdapter;
        app()->instance(FakeSearchDriverAdapter::class, $adapter);
        app()->instance(SearchDriverRegistry::class, new SearchDriverRegistry(app(), ['database' => FakeSearchDriverAdapter::class]));
        app()->instance(QueueWorkerRestartService::class, Mockery::mock(QueueWorkerRestartService::class));
        $actor = User::factory()->create();
        $profile = SearchDriverConfiguration::query()->create(['name' => 'Database', 'driver' => 'database', 'configuration' => [], 'is_enabled' => true, 'created_by' => $actor->id]);

        return [$actor, $profile, $adapter];
    }

    private function externalState(): array
    {
        $adapter = new FakeSearchDriverAdapter(SearchDriverType::Meilisearch);
        app()->instance(FakeSearchDriverAdapter::class, $adapter);
        app()->instance(SearchDriverRegistry::class, new SearchDriverRegistry(app(), ['meilisearch' => FakeSearchDriverAdapter::class]));
        app()->instance(QueueWorkerRestartService::class, Mockery::mock(QueueWorkerRestartService::class));
        $actor = User::factory()->create();
        $connection = InfrastructureConnection::query()->create(['name' => 'Meilisearch', 'type' => 'meilisearch', 'source' => 'managed', 'configuration' => ['host' => 'https://search.test'], 'credentials' => ['api_key' => 'secret'], 'is_enabled' => true, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
        $profile = SearchDriverConfiguration::query()->create(['name' => 'Meilisearch', 'driver' => 'meilisearch', 'infrastructure_connection_id' => $connection->id, 'configuration' => [], 'is_enabled' => true, 'created_by' => $actor->id]);

        return [$actor, $profile, $adapter, $connection];
    }
}

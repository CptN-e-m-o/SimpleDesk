<?php

namespace Tests\Feature\Admin\System\Search;

use App\Enums\Admin\System\SearchConfigurationMode;
use App\Exceptions\Admin\System\Search\InvalidManagedSearchConfigurationException;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\SearchDriverConfiguration;
use App\Models\Admin\System\SearchDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Search\SearchDeploymentConfigurationSnapshot;
use App\Services\Admin\System\Search\SearchRuntimeConfigurator;
use App\Services\Admin\System\Search\SearchRuntimeState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\DatabaseEngine;
use Tests\TestCase;

class SearchRuntimeConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_settings_and_deployment_mode_leave_scout_untouched(): void
    {
        config()->set('scout.driver', 'collection');
        app(SearchRuntimeConfigurator::class)->apply();
        $this->assertSame('collection', config('scout.driver'));
        SearchDriverSettings::query()->create(['id' => 1, 'mode' => SearchConfigurationMode::Deployment]);
        app(SearchRuntimeConfigurator::class)->apply();
        $this->assertSame('collection', config('scout.driver'));
    }

    public function test_managed_database_uses_synthetic_engine(): void
    {
        $configuration = $this->profile('database');
        SearchDriverSettings::query()->create(['id' => 1, 'mode' => 'managed', 'active_configuration_id' => $configuration->id]);
        app(SearchRuntimeConfigurator::class)->apply();
        $this->assertSame('simpledesk-managed', config('scout.driver'));
        $this->assertSame('database', app(SearchRuntimeState::class)->driver());
        $this->assertInstanceOf(DatabaseEngine::class, app(EngineManager::class)->engine('simpledesk-managed'));
    }

    public function test_managed_provider_preserves_index_settings_and_snapshot(): void
    {
        $snapshot = app(SearchDeploymentConfigurationSnapshot::class);
        $original = $snapshot->configuration();
        config()->set('scout.meilisearch.index-settings', ['tickets' => ['filterableAttributes' => ['id']]]);
        $configuration = $this->profile('meilisearch');
        SearchDriverSettings::query()->create(['id' => 1, 'mode' => 'managed', 'active_configuration_id' => $configuration->id]);
        app(SearchRuntimeConfigurator::class)->apply();
        $this->assertSame('https://managed.test', config('scout.meilisearch.host'));
        $this->assertSame(['tickets' => ['filterableAttributes' => ['id']]], config('scout.meilisearch.index-settings'));
        $this->assertSame($original, $snapshot->configuration());
    }

    public function test_corrupt_managed_state_fails_without_fallback(): void
    {
        config()->set('scout.driver', 'database');
        $configuration = $this->profile('database');
        $configuration->update(['is_enabled' => false]);
        SearchDriverSettings::query()->create(['id' => 1, 'mode' => 'managed', 'active_configuration_id' => $configuration->id]);
        $this->expectException(InvalidManagedSearchConfigurationException::class);
        app(SearchRuntimeConfigurator::class)->apply();
    }

    private function profile(string $driver): SearchDriverConfiguration
    {
        $user = User::factory()->create();
        $connectionId = null;
        if ($driver === 'meilisearch') {
            $connectionId = InfrastructureConnection::query()->create(['name' => 'Managed Meili', 'type' => 'meilisearch', 'source' => 'managed', 'configuration' => ['host' => 'https://managed.test'], 'credentials' => ['api_key' => 'managed-secret'], 'is_enabled' => true, 'created_by' => $user->id, 'updated_by' => $user->id])->id;
        }

        return SearchDriverConfiguration::query()->create(['name' => 'Search', 'driver' => $driver, 'infrastructure_connection_id' => $connectionId, 'configuration' => [], 'is_enabled' => true, 'created_by' => $user->id]);
    }
}

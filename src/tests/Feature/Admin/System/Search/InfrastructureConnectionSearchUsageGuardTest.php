<?php

namespace Tests\Feature\Admin\System\Search;

use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\SearchDriverConfiguration;
use App\Models\Admin\System\SearchDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InfrastructureConnectionSearchUsageGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_managed_search_reference_blocks_disable(): void
    {
        [$actor, $connection, $profile] = $this->state();
        SearchDriverSettings::query()->create(['id' => 1, 'mode' => 'managed', 'active_configuration_id' => $profile->id]);
        $this->expectException(ValidationException::class);
        app(InfrastructureConnectionCatalogService::class)->setEnabled($connection, false, $actor);
    }

    public function test_any_search_reference_blocks_infrastructure_force_delete(): void
    {
        [$actor, $connection] = $this->state();
        $connection->delete();
        $this->expectException(ValidationException::class);
        app(InfrastructureConnectionCatalogService::class)->forceDelete($connection->id, $actor);
    }

    private function state(): array
    {
        $actor = User::factory()->create();
        $connection = InfrastructureConnection::query()->create(['name' => 'Meili', 'type' => 'meilisearch', 'source' => 'managed', 'configuration' => ['host' => 'https://search.test'], 'credentials' => ['api_key' => 'secret'], 'is_enabled' => true, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
        $profile = SearchDriverConfiguration::query()->create(['name' => 'Search', 'driver' => 'meilisearch', 'infrastructure_connection_id' => $connection->id, 'configuration' => [], 'is_enabled' => true, 'created_by' => $actor->id]);

        return [$actor, $connection, $profile];
    }
}

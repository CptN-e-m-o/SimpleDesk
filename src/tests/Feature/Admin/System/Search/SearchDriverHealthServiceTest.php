<?php

namespace Tests\Feature\Admin\System\Search;

use App\Data\Admin\System\Search\SearchHealthResultData;
use App\Enums\Admin\System\SearchHealthStatus;
use App\Models\Admin\System\SearchDriverConfiguration;
use App\Models\User\User;
use App\Services\Admin\System\Search\SearchDriverHealthService;
use App\Services\Admin\System\Search\SearchDriverRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\Admin\System\FakeSearchDriverAdapter;
use Tests\TestCase;

class SearchDriverHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_is_persisted_without_adapter_secrets(): void
    {
        $adapter = new FakeSearchDriverAdapter;
        $adapter->result = new SearchHealthResultData(SearchHealthStatus::Healthy, 3, 'Verified.', ['operation' => 'read_only']);
        app()->instance(SearchDriverRegistry::class, new SearchDriverRegistry(app(), ['database' => FakeSearchDriverAdapter::class]));
        app()->instance(FakeSearchDriverAdapter::class, $adapter);
        $actor = User::factory()->create();
        $profile = SearchDriverConfiguration::query()->create(['name' => 'Database', 'driver' => 'database', 'configuration' => [], 'is_enabled' => true, 'created_by' => $actor->id]);
        $result = app(SearchDriverHealthService::class)->test($profile, $actor);
        $this->assertSame('healthy', $result->status->value);
        $this->assertDatabaseHas('search_driver_health_checks', ['search_driver_configuration_id' => $profile->id, 'message' => 'Verified.']);
    }
}

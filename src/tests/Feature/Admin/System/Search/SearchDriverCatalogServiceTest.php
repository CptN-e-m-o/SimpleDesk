<?php

namespace Tests\Feature\Admin\System\Search;

use App\Enums\Admin\System\SearchConfigurationMode;
use App\Models\Admin\System\SearchDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Search\SearchDriverCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SearchDriverCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_profile_rejects_infrastructure_and_restores_disabled(): void
    {
        $actor = User::factory()->create();
        $service = app(SearchDriverCatalogService::class);
        $profile = $service->create(['name' => 'Database', 'driver' => 'database', 'configuration' => [], 'infrastructure_connection_id' => null, 'is_enabled' => true], $actor);
        $service->archive($profile, $actor);
        $restored = $service->restore($profile->id, $actor);
        $this->assertFalse($restored->is_enabled);
        $this->expectException(ValidationException::class);
        $service->create(['name' => 'Invalid', 'driver' => 'database', 'configuration' => [], 'infrastructure_connection_id' => 1, 'is_enabled' => true], $actor);
    }

    public function test_driver_is_immutable_and_active_profile_is_read_only(): void
    {
        $actor = User::factory()->create();
        $service = app(SearchDriverCatalogService::class);
        $profile = $service->create(['name' => 'Database', 'driver' => 'database', 'configuration' => [], 'infrastructure_connection_id' => null, 'is_enabled' => true], $actor);
        SearchDriverSettings::query()->create(['id' => 1, 'mode' => SearchConfigurationMode::Managed, 'active_configuration_id' => $profile->id]);
        $this->expectException(ValidationException::class);
        $service->setEnabled($profile, false, $actor);
    }
}

<?php

namespace Tests\Feature\Admin\System\Search;

use App\Models\Admin\System\SearchDriverConfiguration;
use App\Models\User\User;
use App\Services\Admin\System\Queues\QueueWorkerRestartService;
use App\Services\Admin\System\Search\SearchActivationService;
use App\Services\Admin\System\Search\SearchDriverRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

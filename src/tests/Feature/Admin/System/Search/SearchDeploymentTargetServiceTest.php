<?php

namespace Tests\Feature\Admin\System\Search;

use App\Services\Admin\System\Search\SearchDeploymentConfigurationSnapshot;
use App\Services\Admin\System\Search\SearchDeploymentTargetService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SearchDeploymentTargetServiceTest extends TestCase
{
    public function test_snapshot_drives_target_after_runtime_config_mutation(): void
    {
        $snapshot = new SearchDeploymentConfigurationSnapshot;
        $snapshot->capture(['driver' => 'collection']);
        app()->instance(SearchDeploymentConfigurationSnapshot::class, $snapshot);
        config()->set('scout.driver', 'simpledesk-managed');
        $this->assertSame('collection', app(SearchDeploymentTargetService::class)->resolve()['driver']);
    }

    public function test_unknown_deployment_driver_is_structurally_rejected(): void
    {
        $snapshot = new SearchDeploymentConfigurationSnapshot;
        $snapshot->capture(['driver' => 'unknown']);
        app()->instance(SearchDeploymentConfigurationSnapshot::class, $snapshot);
        $this->expectException(ValidationException::class);
        app(SearchDeploymentTargetService::class)->resolve();
    }
}

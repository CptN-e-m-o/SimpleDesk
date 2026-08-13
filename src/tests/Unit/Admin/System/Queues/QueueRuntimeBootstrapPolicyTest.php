<?php

namespace Tests\Unit\Admin\System\Queues;

use App\Services\Admin\System\Queues\QueueRuntimeBootstrapPolicy;
use Tests\TestCase;

class QueueRuntimeBootstrapPolicyTest extends TestCase
{
    public function test_package_discover_may_boot_without_database(): void
    {
        $policy =
            $this->app->make(
                QueueRuntimeBootstrapPolicy::class,
            );

        $this->assertTrue(
            $policy
                ->maySkipDatabaseInspectionFailure([
                    'artisan',
                    'package:discover',
                    '--ansi',
                ]),
        );
    }

    public function test_queue_worker_may_not_boot_without_database_ownership_check(): void
    {
        $policy =
            $this->app->make(
                QueueRuntimeBootstrapPolicy::class,
            );

        $this->assertFalse(
            $policy
                ->maySkipDatabaseInspectionFailure([
                    'artisan',
                    'queue:work',
                ]),
        );
    }

    public function test_scheduler_may_not_boot_without_database_ownership_check(): void
    {
        $policy =
            $this->app->make(
                QueueRuntimeBootstrapPolicy::class,
            );

        $this->assertFalse(
            $policy
                ->maySkipDatabaseInspectionFailure([
                    'artisan',
                    'schedule:work',
                ]),
        );
    }
}

<?php

namespace Tests\Unit\Admin\System\Runtime;

use App\Services\Admin\System\Runtime\SystemRuntimeBootstrapPolicy;
use Illuminate\Contracts\Foundation\Application;
use Tests\TestCase;

class SystemRuntimeBootstrapPolicyTest extends TestCase
{
    public function test_package_discover_may_boot_without_database(): void
    {
        $policy = $this->policy();

        $this->assertTrue(
            $policy->maySkipDatabaseInspectionFailure([
                'artisan',
                'package:discover',
                '--ansi',
            ]),
        );
    }

    public function test_global_artisan_options_before_package_discover_are_ignored(): void
    {
        $policy = $this->policy();

        $this->assertTrue(
            $policy->maySkipDatabaseInspectionFailure([
                'artisan',
                '--env=production',
                '--ansi',
                'package:discover',
            ]),
        );
    }

    public function test_queue_worker_may_not_boot_without_database_ownership_check(): void
    {
        $policy = $this->policy();

        $this->assertFalse(
            $policy->maySkipDatabaseInspectionFailure([
                'artisan',
                'queue:work',
            ]),
        );
    }

    public function test_scheduler_may_not_boot_without_database_ownership_check(): void
    {
        $policy = $this->policy();

        $this->assertFalse(
            $policy->maySkipDatabaseInspectionFailure([
                'artisan',
                'schedule:work',
            ]),
        );
    }

    public function test_migrations_may_not_skip_database_ownership_check(): void
    {
        $policy = $this->policy();

        $this->assertFalse(
            $policy->maySkipDatabaseInspectionFailure([
                'artisan',
                'migrate',
            ]),
        );
    }

    public function test_http_runtime_never_skips_database_inspection_failure(): void
    {
        $policy = $this->policy(
            runningInConsole: false,
        );

        $this->assertFalse(
            $policy->maySkipDatabaseInspectionFailure([
                'artisan',
                'package:discover',
            ]),
        );
    }

    private function policy(
        bool $runningInConsole = true,
    ): SystemRuntimeBootstrapPolicy {
        $application = $this->createMock(
            Application::class,
        );

        $application
            ->method('runningInConsole')
            ->willReturn($runningInConsole);

        return new SystemRuntimeBootstrapPolicy(
            $application,
        );
    }
}

<?php

namespace Tests\Unit\Admin\System\Queues;

use App\Services\Admin\System\Queues\QueuePinnedWorkloadService;
use Tests\TestCase;

class QueuePinnedWorkloadServiceTest extends TestCase
{
    public function test_enabled_explicit_workload_is_reported(): void
    {
        config()->set('simpledesk-mail-automation.enabled', true);
        config()->set('simpledesk-mail-automation.sync.enabled', true);
        config()->set('simpledesk-mail-automation.sync.queue_connection', 'redis');

        $workloads = app(QueuePinnedWorkloadService::class)->enabled();

        $this->assertTrue(
            collect($workloads)->contains(
                fn (array $workload): bool => $workload['key'] === 'mail_sync'
                    && $workload['connection'] === 'redis',
            ),
        );
    }

    public function test_disabled_explicit_workload_is_ignored(): void
    {
        config()->set('simpledesk-mail-automation.enabled', true);
        config()->set('simpledesk-mail-automation.sync.enabled', false);
        config()->set('simpledesk-mail-automation.sync.queue_connection', 'redis');

        $workloads = app(QueuePinnedWorkloadService::class)->enabled();

        $this->assertFalse(
            collect($workloads)->contains(
                fn (array $workload): bool => $workload['key'] === 'mail_sync',
            ),
        );
    }

    public function test_empty_connection_is_not_reported(): void
    {
        config()->set('simpledesk-mail-automation.enabled', true);
        config()->set('simpledesk-mail-automation.sync.enabled', true);
        config()->set('simpledesk-mail-automation.sync.queue_connection', '');

        $workloads = app(QueuePinnedWorkloadService::class)->enabled();

        $this->assertFalse(
            collect($workloads)->contains(
                fn (array $workload): bool => $workload['key'] === 'mail_sync',
            ),
        );
    }
}

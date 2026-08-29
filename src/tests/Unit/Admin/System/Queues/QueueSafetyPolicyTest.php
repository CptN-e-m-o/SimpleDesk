<?php

namespace Tests\Unit\Admin\System\Queues;

use App\Services\Admin\System\Queues\QueueSafetyPolicy;
use Tests\TestCase;

class QueueSafetyPolicyTest extends TestCase
{
    public function test_minimum_retry_after_uses_worker_timeout_and_margin(): void
    {
        config()->set(
            'simpledesk-queues.worker.max_timeout_seconds',
            300,
        );

        config()->set(
            'simpledesk-queues.worker.retry_safety_margin_seconds',
            30,
        );

        $policy =
            $this->app->make(
                QueueSafetyPolicy::class,
            );

        $this->assertSame(
            330,
            $policy
                ->minimumRetryAfterSeconds(),
        );
    }

    public function test_minimum_changes_with_configuration(): void
    {
        config()->set(
            'simpledesk-queues.worker.max_timeout_seconds',
            120,
        );

        config()->set(
            'simpledesk-queues.worker.retry_safety_margin_seconds',
            15,
        );

        $policy =
            $this->app->make(
                QueueSafetyPolicy::class,
            );

        $this->assertSame(
            135,
            $policy
                ->minimumRetryAfterSeconds(),
        );
    }
}

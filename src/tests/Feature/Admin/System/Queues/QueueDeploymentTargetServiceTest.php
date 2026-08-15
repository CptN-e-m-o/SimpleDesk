<?php

namespace Tests\Feature\Admin\System\Queues;

use App\Services\Admin\System\Queues\QueueDeploymentTargetService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QueueDeploymentTargetServiceTest extends TestCase
{
    public function test_deployment_target_remains_stable_when_runtime_default_changes(): void
    {
        config()->set(
            'simpledesk-queues.deployment.connection',
            'sync',
        );

        config()->set(
            'queue.connections.sync',
            [
                'driver' => 'sync',
            ],
        );

        config()->set(
            'queue.default',
            'simpledesk-managed',
        );

        $service = app(
            QueueDeploymentTargetService::class,
        );

        $target = $service->resolve();

        $this->assertSame(
            'sync',
            $target['connection'],
        );

        $this->assertSame(
            'sync',
            $target['driver'],
        );

        $result = $service->test(
            $target,
        );

        $this->assertSame(
            'healthy',
            $result->status->value,
        );
    }

    public function test_missing_deployment_connection_is_rejected(): void
    {
        config()->set(
            'simpledesk-queues.deployment.connection',
            'missing-deployment',
        );

        config()->set(
            'queue.connections.missing-deployment',
            null,
        );

        $this->expectException(
            ValidationException::class,
        );

        app(
            QueueDeploymentTargetService::class,
        )->resolve();
    }

    public function test_managed_runtime_connection_cannot_be_deployment_target(): void
    {
        config()->set(
            'simpledesk-queues.runtime.connection_name',
            'simpledesk-managed',
        );

        config()->set(
            'simpledesk-queues.deployment.connection',
            'simpledesk-managed',
        );

        config()->set(
            'queue.connections.simpledesk-managed',
            [
                'driver' => 'sync',
            ],
        );

        $this->expectException(
            ValidationException::class,
        );

        app(
            QueueDeploymentTargetService::class,
        )->resolve();
    }
}

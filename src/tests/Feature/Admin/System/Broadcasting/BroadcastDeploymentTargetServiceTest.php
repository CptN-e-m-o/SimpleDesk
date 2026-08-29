<?php

namespace Tests\Feature\Admin\System\Broadcasting;

use App\Services\Admin\System\Broadcasting\BroadcastDeploymentTargetService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BroadcastDeploymentTargetServiceTest extends TestCase
{
    public function test_stable_target_does_not_follow_runtime_default_and_log_null_are_allowed(): void
    {
        config()->set('simpledesk-broadcasting.deployment.connection', 'log');
        config()->set('broadcasting.default', 'simpledesk-managed');
        $target = app(BroadcastDeploymentTargetService::class)->resolve();
        $this->assertSame('log', $target['connection']);
        $this->assertFalse($target['externally_delivering']);
        config()->set('simpledesk-broadcasting.deployment.connection', 'null');
        $this->assertSame('null', app(BroadcastDeploymentTargetService::class)->resolve()['driver']);
    }

    public function test_missing_and_synthetic_targets_are_rejected(): void
    {
        config()->set('simpledesk-broadcasting.deployment.connection', 'simpledesk-managed');
        $this->expectException(ValidationException::class);
        app(BroadcastDeploymentTargetService::class)->resolve();
    }

    public function test_safe_target_excludes_secrets(): void
    {
        config()->set('simpledesk-broadcasting.deployment.connection', 'pusher-test');
        config()->set('broadcasting.connections.pusher-test', ['driver' => 'pusher', 'key' => 'key', 'secret' => 'top-secret', 'app_id' => 'app', 'options' => ['cluster' => 'eu']]);
        $safe = app(BroadcastDeploymentTargetService::class)->safeTarget();
        $this->assertTrue($safe['available']);
        $this->assertArrayNotHasKey('configuration', $safe);
        $this->assertStringNotContainsString('top-secret', json_encode($safe, JSON_THROW_ON_ERROR));
    }
}

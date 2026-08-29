<?php

namespace Tests\Feature\Admin\System\Broadcasting;

use App\Events\Admin\System\Broadcasting\BrowserProbeSent;
use App\Http\Middleware\CheckPermission;
use App\Models\User\User;
use App\Services\Admin\System\Broadcasting\BroadcastClientConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;

class BroadcastBrowserProbeTest extends TestCase
{
    use RefreshDatabase;

    public function test_probe_requires_authentication(): void
    {
        $response = $this->post(
            route('admin.system.broadcasting.browser-probe'),
            [
                'probe_id' => (string) Str::uuid(),
            ],
        );

        $response->assertRedirect(
            route('login'),
        );
    }

    public function test_probe_is_rejected_when_managed_browser_broadcasting_is_unavailable(): void
    {
        Event::fake();

        $user = User::factory()->create();

        $this->withoutMiddleware(
            CheckPermission::class,
        );

        $this->mock(
            BroadcastClientConfigurationService::class,
            function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('effective')
                    ->once()
                    ->andReturn([
                        'available' => false,
                        'ownership' => 'deployment',
                        'message' => 'Deployment client metadata is not managed by SimpleDesk.',
                    ]);
            },
        );

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('admin.system.broadcasting.browser-probe'),
                [
                    'probe_id' => (string) Str::uuid(),
                ],
            );

        $response->assertStatus(409);

        Event::assertNotDispatched(
            BrowserProbeSent::class,
        );
    }

    public function test_probe_dispatches_private_browser_event_for_authenticated_user(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $probeId = (string) Str::uuid();

        $this->withoutMiddleware(
            CheckPermission::class,
        );

        $this->mock(
            BroadcastClientConfigurationService::class,
            function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('effective')
                    ->once()
                    ->andReturn([
                        'available' => true,
                        'ownership' => 'managed',
                    ]);
            },
        );

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('admin.system.broadcasting.browser-probe'),
                [
                    'probe_id' => $probeId,
                ],
            );

        $response
            ->assertOk()
            ->assertJson([
                'probe_id' => $probeId,
            ]);

        Event::assertDispatched(
            BrowserProbeSent::class,
            function (BrowserProbeSent $event) use ($user, $probeId): bool {
                return $event->userId === $user->id
                    && $event->probeId === $probeId;
            },
        );
    }

    public function test_probe_is_rate_limited(): void
    {
        Event::fake();

        $user = User::factory()->create();

        $this->withoutMiddleware(
            CheckPermission::class,
        );

        $this->mock(
            BroadcastClientConfigurationService::class,
            function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('effective')
                    ->times(10)
                    ->andReturn([
                        'available' => true,
                        'ownership' => 'managed',
                    ]);
            },
        );

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this
                ->actingAs($user)
                ->postJson(
                    route('admin.system.broadcasting.browser-probe'),
                    [
                        'probe_id' => (string) Str::uuid(),
                    ],
                )
                ->assertOk();
        }

        $this
            ->actingAs($user)
            ->postJson(
                route('admin.system.broadcasting.browser-probe'),
                [
                    'probe_id' => (string) Str::uuid(),
                ],
            )
            ->assertStatus(429);
    }
}

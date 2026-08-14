<?php

namespace Tests\Feature\Admin\System\Queues;

use App\Enums\Admin\System\QueueDriverType;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User\User;
use App\Services\Admin\System\Queues\QueueBacklogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class QueueDriverConfigurationHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_queue_view_permission(): void
    {
        $this
            ->actingAs(
                User::factory()->create(),
            )
            ->get(
                route(
                    'admin.system.queues.index',
                ),
            )
            ->assertForbidden();
    }

    public function test_index_exposes_operational_contract_for_future_frontend(): void
    {
        config()->set(
            'queue.default',
            'sync',
        );

        config()->set(
            'queue.connections.sync.driver',
            'sync',
        );

        $configuration = QueueDriverConfiguration::query()
            ->create([
                'name' => 'Synchronous',
                'driver' => QueueDriverType::Sync,
                'configuration' => [],
                'is_enabled' => true,
            ]);

        $backlog = $this->createMock(
            QueueBacklogService::class,
        );

        $backlog
            ->method('inspect')
            ->willReturn([
                'queues' => [],
                'total_pending' => 0,
                'has_errors' => false,
                'inspected_at' =>
                    '2026-08-14T00:00:00+00:00',
            ]);

        $this->app->instance(
            QueueBacklogService::class,
            $backlog,
        );

        $response = $this
            ->actingAs(
                $this->user([
                    'admin.settings.queues.view',
                ]),
            )
            ->get(
                route(
                    'admin.system.queues.index',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component(
                        'Admin/System/Queues/Index',
                    )
                    ->where(
                        'ownership.mode',
                        'deployment',
                    )
                    ->where(
                        'ownership.owned',
                        false,
                    )
                    ->where(
                        'ownership.worker_restart_required',
                        false,
                    )
                    ->where(
                        'effective_connection',
                        'sync',
                    )
                    ->where(
                        'effective_driver',
                        'sync',
                    )
                    ->where(
                        'active_configuration',
                        null,
                    )
                    ->has(
                        'configurations.data',
                        1,
                    )
                    ->where(
                        'configurations.data.0.id',
                        $configuration->id,
                    )
                    ->where(
                        'configurations.data.0.infrastructure_connection_id',
                        null,
                    )
                    ->has(
                        'definitions',
                        3,
                    )
                    ->where(
                        'definitions.0.type',
                        'database',
                    )
                    ->where(
                        'definitions.1.type',
                        'redis',
                    )
                    ->where(
                        'definitions.2.type',
                        'sync',
                    )
                    ->has(
                        'workloads',
                    )
                    ->where(
                        'backlog.total_pending',
                        0,
                    )
                    ->where(
                        'backlog.has_errors',
                        false,
                    )
                    ->has(
                        'filters',
                    )
                    ->where(
                        'stats.total',
                        1,
                    )
                    ->where(
                        'stats.enabled',
                        1,
                    )
                    ->where(
                        'stats.archived',
                        0,
                    ),
            );
    }

    public function test_create_page_exposes_only_selectable_redis_connections_and_queue_defaults(): void
    {
        $enabled = InfrastructureConnection::factory()
            ->create([
                'name' => 'Selectable Redis',
                'is_enabled' => true,
            ]);

        InfrastructureConnection::factory()
            ->create([
                'name' => 'Disabled Redis',
                'is_enabled' => false,
            ]);

        $archived = InfrastructureConnection::factory()
            ->create([
                'name' => 'Archived Redis',
                'is_enabled' => true,
            ]);

        $archived->delete();

        $response = $this
            ->actingAs(
                $this->user([
                    'admin.settings.queues.create',
                ]),
            )
            ->get(
                route(
                    'admin.system.queues.create',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component(
                        'Admin/System/Queues/Create',
                    )
                    ->has(
                        'definitions',
                        3,
                    )
                    ->has(
                        'redis_connections',
                        1,
                    )
                    ->where(
                        'redis_connections.0.id',
                        $enabled->id,
                    )
                    ->where(
                        'redis_connections.0.name',
                        'Selectable Redis',
                    )
                    ->where(
                        'redis_connections.0.type',
                        'redis',
                    )
                    ->where(
                        'defaults.minimum_retry_after',
                        330,
                    ),
            );
    }

    public function test_edit_page_keeps_referenced_unavailable_redis_connection_visible(): void
    {
        $infrastructure = InfrastructureConnection::factory()
            ->create([
                'name' => 'Previously selected Redis',
                'is_enabled' => true,
            ]);

        $configuration = QueueDriverConfiguration::query()
            ->create([
                'name' => 'Redis Queue',
                'driver' => QueueDriverType::Redis,

                'infrastructure_connection_id' =>
                    $infrastructure->id,

                'configuration' => [
                    'retry_after' => 360,
                    'block_for' => 5,
                    'after_commit' => false,
                ],

                'is_enabled' => true,
            ]);

        $infrastructure
            ->forceFill([
                'is_enabled' => false,
            ])
            ->save();

        $infrastructure->delete();

        $response = $this
            ->actingAs(
                $this->user([
                    'admin.settings.queues.update',
                ]),
            )
            ->get(
                route(
                    'admin.system.queues.edit',
                    $configuration,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component(
                        'Admin/System/Queues/Edit',
                    )
                    ->where(
                        'configuration.id',
                        $configuration->id,
                    )
                    ->where(
                        'configuration.infrastructure_connection_id',
                        $infrastructure->id,
                    )
                    ->missing(
                        'configuration.configuration.infrastructure_connection_id',
                    )
                    ->has(
                        'redis_connections',
                        1,
                    )
                    ->where(
                        'redis_connections.0.id',
                        $infrastructure->id,
                    )
                    ->where(
                        'redis_connections.0.type',
                        'redis',
                    )
                    ->where(
                        'redis_connections.0.is_enabled',
                        false,
                    )
                    ->where(
                        'redis_connections.0.deleted_at',
                        fn ($value) =>
                            is_string($value)
                            && $value !== '',
                    ),
            );
    }

    public function test_queue_management_routes_enforce_expected_permissions(): void
    {
        $expected = [
            'admin.system.queues.index' =>
                'permission:admin.settings.queues.view',

            'admin.system.queues.create' =>
                'permission:admin.settings.queues.create',

            'admin.system.queues.store' =>
                'permission:admin.settings.queues.create',

            'admin.system.queues.edit' =>
                'permission:admin.settings.queues.update',

            'admin.system.queues.update' =>
                'permission:admin.settings.queues.update',

            'admin.system.queues.enabled' =>
                'permission:admin.settings.queues.update',

            'admin.system.queues.destroy' =>
                'permission:admin.settings.queues.archive',

            'admin.system.queues.restore' =>
                'permission:admin.settings.queues.archive',

            'admin.system.queues.force-delete' =>
                'permission:admin.settings.queues.delete',

            'admin.system.queues.test' =>
                'permission:admin.settings.queues.test',
        ];

        foreach (
            $expected as $name => $middleware
        ) {
            $route = Route::getRoutes()
                ->getByName(
                    $name,
                );

            $this->assertNotNull(
                $route,
                "Route [{$name}] is missing.",
            );

            $this->assertContains(
                $middleware,
                $route->gatherMiddleware(),
                "Route [{$name}] has incorrect permission middleware.",
            );
        }

        $this->assertNull(
            Route::getRoutes()
                ->getByName(
                    'admin.system.queues.activate',
                ),
        );
    }

    public function test_user_without_queue_permissions_cannot_mutate_or_test_configurations(): void
    {
        $user = User::factory()->create();

        $configuration = QueueDriverConfiguration::query()
            ->create([
                'name' => 'Protected Queue',
                'driver' => QueueDriverType::Sync,
                'configuration' => [],
                'is_enabled' => true,
            ]);

        $archived = QueueDriverConfiguration::query()
            ->create([
                'name' => 'Archived Queue',
                'driver' => QueueDriverType::Sync,
                'configuration' => [],
                'is_enabled' => false,
            ]);

        $archived->delete();

        $payload = [
            'name' => 'Queue',
            'driver' => 'sync',
            'infrastructure_connection_id' => null,
            'configuration' => [],
            'is_enabled' => true,
        ];

        $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.system.queues.create',
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.queues.store',
                ),
                $payload,
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.system.queues.edit',
                    $configuration,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->put(
                route(
                    'admin.system.queues.update',
                    $configuration,
                ),
                $payload,
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'admin.system.queues.enabled',
                    $configuration,
                ),
                [
                    'is_enabled' => false,
                ],
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'admin.system.queues.destroy',
                    $configuration,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.queues.restore',
                    $archived->id,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'admin.system.queues.force-delete',
                    $archived->id,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.queues.test',
                    $configuration,
                ),
            )
            ->assertForbidden();
    }

    private function user(
        array $permissionKeys,
    ): User {
        $user = User::factory()->create();

        $group = PermissionGroup::query()
            ->create([
                'key' => 'queues-'.$user->id,
                'label' => 'Queues',
                'panel' => 'admin',
                'type' => 'agent',
                'sort_order' => 1,
            ]);

        $role = Role::query()
            ->create([
                'name' => 'queues-'.$user->id,
                'label' => 'Queues',
                'type' => 'user',
            ]);

        $permissionIds = collect(
            $permissionKeys,
        )
            ->map(
                fn (string $key): int =>
                Permission::query()
                    ->create([
                        'permission_group_id' =>
                            $group->id,

                        'key' =>
                            $key,

                        'label' =>
                            $key,

                        'type' =>
                            'agent',

                        'ui_type' =>
                            'checkbox',

                        'sort_order' =>
                            1,
                    ])
                    ->id,
            );

        $role
            ->permissions()
            ->sync(
                $permissionIds,
            );

        $user
            ->roles()
            ->attach(
                $role,
            );

        return $user;
    }
}

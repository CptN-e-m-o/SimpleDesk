<?php

namespace Tests\Feature\Admin\System\Broadcasting;

use App\Enums\Admin\System\BroadcastConfigurationMode;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Models\Admin\System\BroadcastDriverConfiguration;
use App\Models\Admin\System\BroadcastDriverSettings;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BroadcastDriverHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_broadcast_view_permission(): void
    {
        $this
            ->actingAs(User::factory()->create())
            ->get(route('admin.system.broadcasting.index'))
            ->assertForbidden();
    }

    public function test_broadcast_management_routes_enforce_expected_permissions(): void
    {
        $expected = [
            'admin.system.broadcasting.index' => 'permission:admin.settings.broadcasting.view',
            'admin.system.broadcasting.create' => 'permission:admin.settings.broadcasting.create',
            'admin.system.broadcasting.store' => 'permission:admin.settings.broadcasting.create',
            'admin.system.broadcasting.edit' => 'permission:admin.settings.broadcasting.update',
            'admin.system.broadcasting.update' => 'permission:admin.settings.broadcasting.update',
            'admin.system.broadcasting.enabled' => 'permission:admin.settings.broadcasting.update',
            'admin.system.broadcasting.destroy' => 'permission:admin.settings.broadcasting.archive',
            'admin.system.broadcasting.restore' => 'permission:admin.settings.broadcasting.archive',
            'admin.system.broadcasting.force-delete' => 'permission:admin.settings.broadcasting.delete',
            'admin.system.broadcasting.test' => 'permission:admin.settings.broadcasting.test',
            'admin.system.broadcasting.activate' => 'permission:admin.settings.broadcasting.activate',
            'admin.system.broadcasting.force-activate' => 'permission:admin.settings.broadcasting.force_activate',
            'admin.system.broadcasting.activate-deployment' => 'permission:admin.settings.broadcasting.activate',
            'admin.system.broadcasting.force-activate-deployment' => 'permission:admin.settings.broadcasting.force_activate',
        ];

        foreach ($expected as $name => $middleware) {
            $route = Route::getRoutes()->getByName($name);

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
    }

    public function test_user_without_broadcast_permissions_cannot_manage_broadcasting(): void
    {
        $user = User::factory()->create();
        $configuration = $this->configuration();
        $archived = $this->configuration('Archived Reverb', false);
        $archived->delete();

        $payload = [
            'name' => 'Protected Reverb',
            'driver' => 'reverb',
            'infrastructure_connection_id' => $configuration->infrastructure_connection_id,
            'configuration' => [],
            'is_enabled' => true,
        ];

        $requests = [
            ['GET', route('admin.system.broadcasting.index'), []],
            ['GET', route('admin.system.broadcasting.create'), []],
            ['POST', route('admin.system.broadcasting.store'), $payload],
            ['GET', route('admin.system.broadcasting.edit', $configuration), []],
            ['PUT', route('admin.system.broadcasting.update', $configuration), $payload],
            ['PATCH', route('admin.system.broadcasting.enabled', $configuration), ['is_enabled' => false]],
            ['DELETE', route('admin.system.broadcasting.destroy', $configuration), []],
            ['POST', route('admin.system.broadcasting.restore', $archived->id), []],
            ['DELETE', route('admin.system.broadcasting.force-delete', $archived->id), []],
            ['POST', route('admin.system.broadcasting.test', $configuration), []],
            ['POST', route('admin.system.broadcasting.activate', $configuration), []],
            ['POST', route('admin.system.broadcasting.force-activate', $configuration), []],
            ['POST', route('admin.system.broadcasting.activate-deployment'), []],
            ['POST', route('admin.system.broadcasting.force-activate-deployment'), []],
        ];

        foreach ($requests as [$method, $uri, $data]) {
            $this
                ->actingAs($user)
                ->call($method, $uri, $data)
                ->assertForbidden();
        }
    }

    public function test_normal_activation_permission_does_not_grant_force_activation(): void
    {
        $configuration = $this->configuration();

        $user = $this->user([
            'admin.settings.broadcasting.activate',
        ]);

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.broadcasting.force-activate',
                    $configuration,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.broadcasting.force-activate-deployment',
                ),
            )
            ->assertForbidden();
    }

    public function test_force_activation_permission_does_not_grant_normal_activation(): void
    {
        $configuration = $this->configuration();

        $user = $this->user([
            'admin.settings.broadcasting.force_activate',
        ]);

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.broadcasting.activate',
                    $configuration,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.broadcasting.activate-deployment',
                ),
            )
            ->assertForbidden();
    }

    public function test_create_page_exposes_only_selectable_broadcast_connections(): void
    {
        $actor = User::factory()->create();

        $reverb = $this->connection(
            actor: $actor,
            name: 'Selectable Reverb',
        );

        $pusher = $this->connection(
            actor: $actor,
            type: InfrastructureConnectionType::Pusher,
            name: 'Selectable Pusher',
        );

        $this->connection(
            actor: $actor,
            name: 'Disabled Reverb',
            enabled: false,
        );

        $archived = $this->connection(
            actor: $actor,
            name: 'Archived Reverb',
        );

        $archived->delete();

        InfrastructureConnection::factory()->create([
            'name' => 'Unrelated Redis',
            'is_enabled' => true,
        ]);

        $response = $this
            ->actingAs(
                $this->user([
                    'admin.settings.broadcasting.create',
                ]),
            )
            ->get(
                route(
                    'admin.system.broadcasting.create',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/System/Broadcasting/Form')
                    ->where('configuration', null)
                    ->has('definitions', 3)
                    ->has('connections', 2)
                    ->where('connections.0.id', function ($value) use ($reverb, $pusher) {
                        return in_array($value, [$reverb->id, $pusher->id], true);
                    })
                    ->where('connections.1.id', function ($value) use ($reverb, $pusher) {
                        return in_array($value, [$reverb->id, $pusher->id], true);
                    }),
            );
    }

    public function test_edit_page_keeps_referenced_unavailable_connection_visible(): void
    {
        $actor = User::factory()->create();

        $connection = $this->connection(
            actor: $actor,
            name: 'Previously Selected Reverb',
        );

        $configuration = $this->configuration(
            name: 'Reverb Profile',
            connection: $connection,
        );

        $connection
            ->forceFill([
                'is_enabled' => false,
            ])
            ->save();

        $connection->delete();

        $response = $this
            ->actingAs(
                $this->user([
                    'admin.settings.broadcasting.update',
                ]),
            )
            ->get(
                route(
                    'admin.system.broadcasting.edit',
                    $configuration,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/System/Broadcasting/Form')
                    ->where('configuration.id', $configuration->id)
                    ->where(
                        'configuration.infrastructure_connection_id',
                        $connection->id,
                    )
                    ->missing(
                        'configuration.configuration.infrastructure_connection_id',
                    )
                    ->has('connections', 1)
                    ->where('connections.0.id', $connection->id)
                    ->where('connections.0.is_enabled', false)
                    ->where(
                        'connections.0.deleted_at',
                        fn ($value) => is_string($value) && $value !== '',
                    ),
            );
    }

    public function test_http_store_creates_reverb_profile(): void
    {
        $actor = User::factory()->create();
        $connection = $this->connection($actor);

        $user = $this->user([
            'admin.settings.broadcasting.create',
        ]);

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.broadcasting.store',
                ),
                [
                    'name' => 'HTTP Reverb',
                    'driver' => 'reverb',
                    'infrastructure_connection_id' => $connection->id,
                    'configuration' => [],
                    'is_enabled' => true,
                ],
            )
            ->assertRedirect(
                route(
                    'admin.system.broadcasting.index',
                ),
            );

        $configuration = BroadcastDriverConfiguration::query()
            ->where('name', 'HTTP Reverb')
            ->firstOrFail();

        $this->assertSame('reverb', $configuration->driver->value);
        $this->assertSame(
            $connection->id,
            $configuration->infrastructure_connection_id,
        );
        $this->assertSame([], $configuration->configuration);
        $this->assertTrue($configuration->is_enabled);
        $this->assertSame($user->id, $configuration->created_by);
        $this->assertSame($user->id, $configuration->updated_by);
    }

    public function test_http_store_rejects_nested_infrastructure_connection_id(): void
    {
        $actor = User::factory()->create();
        $connection = $this->connection($actor);

        $this
            ->actingAs(
                $this->user([
                    'admin.settings.broadcasting.create',
                ]),
            )
            ->post(
                route(
                    'admin.system.broadcasting.store',
                ),
                [
                    'name' => 'Invalid Reverb',
                    'driver' => 'reverb',
                    'infrastructure_connection_id' => $connection->id,
                    'configuration' => [
                        'infrastructure_connection_id' => $connection->id,
                    ],
                    'is_enabled' => true,
                ],
            )
            ->assertSessionHasErrors([
                'configuration',
                'configuration.infrastructure_connection_id',
            ]);

        $this->assertFalse(
            BroadcastDriverConfiguration::query()
                ->where('name', 'Invalid Reverb')
                ->exists(),
        );
    }

    public function test_active_configuration_update_returns_validation_error_instead_of_server_error(): void
    {
        $actor = $this->user([
            'admin.settings.broadcasting.update',
        ]);

        $configuration = $this->configuration();

        $this->activate(
            $configuration,
            $actor,
        );

        $this
            ->actingAs($actor)
            ->put(
                route(
                    'admin.system.broadcasting.update',
                    $configuration,
                ),
                [
                    'name' => 'Mutated Reverb',
                    'driver' => 'reverb',
                    'infrastructure_connection_id' => $configuration->infrastructure_connection_id,
                    'configuration' => [],
                    'is_enabled' => true,
                ],
            )
            ->assertRedirect()
            ->assertSessionHasErrors([
                'configuration',
            ]);

        $this->assertSame(
            'Protected Reverb',
            $configuration->fresh()->name,
        );
    }

    public function test_active_configuration_cannot_be_disabled_or_archived_over_http(): void
    {
        $actor = $this->user([
            'admin.settings.broadcasting.update',
            'admin.settings.broadcasting.archive',
        ]);

        $configuration = $this->configuration();

        $this->activate(
            $configuration,
            $actor,
        );

        $this
            ->actingAs($actor)
            ->patch(
                route(
                    'admin.system.broadcasting.enabled',
                    $configuration,
                ),
                [
                    'is_enabled' => false,
                ],
            )
            ->assertRedirect()
            ->assertSessionHasErrors([
                'configuration',
            ]);

        $this->assertTrue(
            $configuration->fresh()->is_enabled,
        );

        $this
            ->actingAs($actor)
            ->delete(
                route(
                    'admin.system.broadcasting.destroy',
                    $configuration,
                ),
            )
            ->assertRedirect()
            ->assertSessionHasErrors([
                'configuration',
            ]);

        $this->assertNull(
            $configuration->fresh()->deleted_at,
        );
    }

    private function configuration(
        string $name = 'Protected Reverb',
        bool $enabled = true,
        ?InfrastructureConnection $connection = null,
    ): BroadcastDriverConfiguration {
        $actor = User::factory()->create();
        $connection ??= $this->connection($actor);

        return BroadcastDriverConfiguration::query()->create([
            'name' => $name,
            'driver' => 'reverb',
            'infrastructure_connection_id' => $connection->id,
            'configuration' => [],
            'is_enabled' => $enabled,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function connection(
        User $actor,
        InfrastructureConnectionType $type = InfrastructureConnectionType::Reverb,
        string $name = 'Test Reverb',
        bool $enabled = true,
    ): InfrastructureConnection {
        $configuration = match ($type) {
            InfrastructureConnectionType::Reverb => [
                'app_id' => 'simpledesk-test',
                'host' => '127.0.0.1',
                'port' => 8080,
                'scheme' => 'http',
                'cluster' => '',
                'public_host' => '',
                'public_port' => null,
                'public_scheme' => '',
            ],
            InfrastructureConnectionType::Pusher => [
                'app_id' => 'simpledesk-test',
                'host' => '',
                'port' => 443,
                'scheme' => 'https',
                'cluster' => 'eu',
                'public_host' => '',
                'public_port' => null,
                'public_scheme' => '',
            ],
            default => throw new \LogicException(
                'Unsupported test infrastructure type.',
            ),
        };

        return InfrastructureConnection::query()->create([
            'name' => $name,
            'type' => $type,
            'source' => InfrastructureConnectionSource::Managed,
            'configuration' => $configuration,
            'credentials' => [
                'app_key' => 'simpledesk-test-key',
                'app_secret' => 'simpledesk-test-secret',
            ],
            'is_enabled' => $enabled,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function activate(
        BroadcastDriverConfiguration $configuration,
        User $actor,
    ): void {
        BroadcastDriverSettings::query()->create([
            'id' => BroadcastDriverSettings::SINGLETON_ID,
            'mode' => BroadcastConfigurationMode::Managed,
            'active_configuration_id' => $configuration->id,
            'activated_at' => now(),
            'activated_by' => $actor->id,
        ]);
    }

    private function user(array $permissionKeys): User
    {
        $user = User::factory()->create();

        $group = PermissionGroup::query()->create([
            'key' => 'broadcasting-'.$user->id,
            'label' => 'Broadcasting',
            'panel' => 'admin',
            'type' => 'agent',
            'sort_order' => 1,
        ]);

        $role = Role::query()->create([
            'name' => 'broadcasting-'.$user->id,
            'label' => 'Broadcasting',
            'type' => 'user',
        ]);

        $permissionIds = collect($permissionKeys)
            ->map(
                fn (string $key): int => Permission::query()->create([
                    'permission_group_id' => $group->id,
                    'key' => $key,
                    'label' => $key,
                    'type' => 'agent',
                    'ui_type' => 'checkbox',
                    'sort_order' => 1,
                ])->id,
            );

        $role
            ->permissions()
            ->sync($permissionIds);

        $user
            ->roles()
            ->attach($role);

        return $user;
    }
}

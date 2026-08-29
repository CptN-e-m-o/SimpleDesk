<?php

namespace Tests\Feature\Admin\System\Cache;

use App\Enums\Admin\System\CacheConfigurationMode;
use App\Enums\Admin\System\CacheDriverType;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\CacheDriverSettings;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CacheDriverConfigurationHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_cache_view_permission(): void
    {
        $this
            ->actingAs(
                User::factory()->create(),
            )
            ->get(
                route(
                    'admin.system.cache.index',
                ),
            )
            ->assertForbidden();
    }

    public function test_cache_management_routes_enforce_expected_permissions(): void
    {
        $expected = [
            'admin.system.cache.index' => 'permission:admin.settings.cache.view',
            'admin.system.cache.create' => 'permission:admin.settings.cache.create',
            'admin.system.cache.store' => 'permission:admin.settings.cache.create',
            'admin.system.cache.edit' => 'permission:admin.settings.cache.update',
            'admin.system.cache.update' => 'permission:admin.settings.cache.update',
            'admin.system.cache.enabled' => 'permission:admin.settings.cache.update',
            'admin.system.cache.destroy' => 'permission:admin.settings.cache.archive',
            'admin.system.cache.restore' => 'permission:admin.settings.cache.archive',
            'admin.system.cache.force-delete' => 'permission:admin.settings.cache.delete',
            'admin.system.cache.test' => 'permission:admin.settings.cache.test',
            'admin.system.cache.activate' => 'permission:admin.settings.cache.activate',
            'admin.system.cache.force-activate' => 'permission:admin.settings.cache.force_activate',
            'admin.system.cache.activate-deployment' => 'permission:admin.settings.cache.activate',
            'admin.system.cache.force-activate-deployment' => 'permission:admin.settings.cache.force_activate',
        ];

        foreach ($expected as $name => $middleware) {
            $route = Route::getRoutes()->getByName(
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
    }

    public function test_user_without_cache_permissions_cannot_manage_cache(): void
    {
        $user = User::factory()->create();

        $configuration = $this->configuration();

        $archived = $this->configuration(
            name: 'Archived Cache',
            enabled: false,
        );

        $archived->delete();

        $payload = [
            'name' => 'Protected Cache',
            'driver' => 'file',
            'infrastructure_connection_id' => null,
            'configuration' => [],
            'is_enabled' => true,
        ];

        $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.system.cache.create',
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.cache.store',
                ),
                $payload,
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.system.cache.edit',
                    $configuration,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->put(
                route(
                    'admin.system.cache.update',
                    $configuration,
                ),
                $payload,
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->patch(
                route(
                    'admin.system.cache.enabled',
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
                    'admin.system.cache.destroy',
                    $configuration,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.cache.restore',
                    $archived->id,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->delete(
                route(
                    'admin.system.cache.force-delete',
                    $archived->id,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.cache.test',
                    $configuration,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.cache.activate',
                    $configuration,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.cache.force-activate',
                    $configuration,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.cache.activate-deployment',
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.cache.force-activate-deployment',
                ),
            )
            ->assertForbidden();
    }

    public function test_normal_activation_permission_does_not_grant_force_activation(): void
    {
        $configuration = $this->configuration();

        $user = $this->user([
            'admin.settings.cache.activate',
        ]);

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.cache.force-activate',
                    $configuration,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.cache.force-activate-deployment',
                ),
            )
            ->assertForbidden();
    }

    public function test_force_activation_permission_does_not_grant_normal_activation(): void
    {
        $configuration = $this->configuration();

        $user = $this->user([
            'admin.settings.cache.force_activate',
        ]);

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.cache.activate',
                    $configuration,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'admin.system.cache.activate-deployment',
                ),
            )
            ->assertForbidden();
    }

    public function test_create_page_exposes_only_selectable_redis_connections(): void
    {
        $enabled = InfrastructureConnection::factory()->create([
            'name' => 'Selectable Redis',
            'is_enabled' => true,
        ]);

        InfrastructureConnection::factory()->create([
            'name' => 'Disabled Redis',
            'is_enabled' => false,
        ]);

        $archived = InfrastructureConnection::factory()->create([
            'name' => 'Archived Redis',
            'is_enabled' => true,
        ]);

        $archived->delete();

        $response = $this
            ->actingAs(
                $this->user([
                    'admin.settings.cache.create',
                ]),
            )
            ->get(
                route(
                    'admin.system.cache.create',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component(
                        'Admin/System/Cache/Create',
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
                        'redis_connections.0.is_enabled',
                        true,
                    )
                    ->where(
                        'redis_connections.0.deleted_at',
                        null,
                    ),
            );
    }

    public function test_edit_page_keeps_referenced_unavailable_redis_connection_visible(): void
    {
        $connection = InfrastructureConnection::factory()->create([
            'name' => 'Previously Selected Redis',
            'is_enabled' => true,
        ]);

        $configuration = CacheDriverConfiguration::query()->create([
            'name' => 'Redis Cache',
            'driver' => CacheDriverType::Redis,
            'infrastructure_connection_id' => $connection->id,
            'configuration' => [],
            'is_enabled' => true,
        ]);

        $connection
            ->forceFill([
                'is_enabled' => false,
            ])
            ->save();

        $connection->delete();

        $response = $this
            ->actingAs(
                $this->user([
                    'admin.settings.cache.update',
                ]),
            )
            ->get(
                route(
                    'admin.system.cache.edit',
                    $configuration,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component(
                        'Admin/System/Cache/Edit',
                    )
                    ->where(
                        'configuration.id',
                        $configuration->id,
                    )
                    ->where(
                        'configuration.infrastructure_connection_id',
                        $connection->id,
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
                        $connection->id,
                    )
                    ->where(
                        'redis_connections.0.is_enabled',
                        false,
                    )
                    ->where(
                        'redis_connections.0.deleted_at',
                        fn ($value) => is_string($value)
                            && $value !== '',
                    ),
            );
    }

    public function test_http_store_rejects_nested_infrastructure_connection_id(): void
    {
        $connection = InfrastructureConnection::factory()->create();

        $this
            ->actingAs(
                $this->user([
                    'admin.settings.cache.create',
                ]),
            )
            ->post(
                route(
                    'admin.system.cache.store',
                ),
                [
                    'name' => 'Redis Cache',
                    'driver' => 'redis',
                    'infrastructure_connection_id' => $connection->id,
                    'configuration' => [
                        'infrastructure_connection_id' => $connection->id,
                    ],
                    'is_enabled' => true,
                ],
            )
            ->assertSessionHasErrors([
                'configuration.infrastructure_connection_id',
            ]);

        $this->assertFalse(
            CacheDriverConfiguration::query()
                ->where(
                    'name',
                    'Redis Cache',
                )
                ->exists(),
        );
    }

    public function test_active_configuration_update_returns_validation_error_instead_of_server_error(): void
    {
        $actor = $this->user([
            'admin.settings.cache.update',
        ]);

        $configuration = $this->configuration();

        CacheDriverSettings::query()->create([
            'id' => CacheDriverSettings::SINGLETON_ID,
            'mode' => CacheConfigurationMode::Managed,
            'active_configuration_id' => $configuration->id,
            'activated_at' => now(),
            'activated_by' => $actor->id,
        ]);

        $this
            ->actingAs($actor)
            ->put(
                route(
                    'admin.system.cache.update',
                    $configuration,
                ),
                [
                    'name' => 'Mutated Cache',
                    'driver' => 'file',
                    'infrastructure_connection_id' => null,
                    'configuration' => [],
                    'is_enabled' => true,
                ],
            )
            ->assertRedirect()
            ->assertSessionHasErrors([
                'configuration',
            ]);

        $this->assertSame(
            'Protected Cache',
            $configuration->fresh()->name,
        );
    }

    public function test_active_configuration_cannot_be_disabled_or_archived_over_http(): void
    {
        $actor = $this->user([
            'admin.settings.cache.update',
            'admin.settings.cache.archive',
        ]);

        $configuration = $this->configuration();

        CacheDriverSettings::query()->create([
            'id' => CacheDriverSettings::SINGLETON_ID,
            'mode' => CacheConfigurationMode::Managed,
            'active_configuration_id' => $configuration->id,
            'activated_at' => now(),
            'activated_by' => $actor->id,
        ]);

        $this
            ->actingAs($actor)
            ->patch(
                route(
                    'admin.system.cache.enabled',
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
                    'admin.system.cache.destroy',
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
        string $name = 'Protected Cache',
        bool $enabled = true,
    ): CacheDriverConfiguration {
        return CacheDriverConfiguration::query()->create([
            'name' => $name,
            'driver' => CacheDriverType::File,
            'infrastructure_connection_id' => null,
            'configuration' => [],
            'is_enabled' => $enabled,
        ]);
    }

    private function user(
        array $permissionKeys,
    ): User {
        $user = User::factory()->create();

        $group = PermissionGroup::query()->create([
            'key' => 'cache-'.$user->id,
            'label' => 'Cache',
            'panel' => 'admin',
            'type' => 'agent',
            'sort_order' => 1,
        ]);

        $role = Role::query()->create([
            'name' => 'cache-'.$user->id,
            'label' => 'Cache',
            'type' => 'user',
        ]);

        $permissionIds = collect(
            $permissionKeys,
        )
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

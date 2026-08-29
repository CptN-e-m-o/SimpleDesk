<?php

namespace Tests\Feature\Admin\System\Queues;

use App\Enums\Admin\System\QueueDriverType;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class QueueDriverActivationHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_activation_routes_use_separate_permissions(): void
    {
        $activate = Route::getRoutes()
            ->getByName(
                'admin.system.queues.activate',
            );

        $forceActivate = Route::getRoutes()
            ->getByName(
                'admin.system.queues.force-activate',
            );

        $this->assertNotNull($activate);
        $this->assertNotNull($forceActivate);

        $this->assertContains(
            'permission:admin.settings.queues.activate',
            $activate->gatherMiddleware(),
        );

        $this->assertContains(
            'permission:admin.settings.queues.force_activate',
            $forceActivate->gatherMiddleware(),
        );
    }

    public function test_user_without_activation_permission_cannot_activate_configuration(): void
    {
        $configuration = $this->configuration();

        $this->actingAs(
            User::factory()->create(),
        )
            ->post(
                route(
                    'admin.system.queues.activate',
                    $configuration,
                ),
            )
            ->assertForbidden();
    }

    public function test_normal_activation_permission_does_not_allow_force_activation(): void
    {
        $configuration = $this->configuration();

        $user = $this->user([
            'admin.settings.queues.activate',
        ]);

        $this->actingAs($user)
            ->post(
                route(
                    'admin.system.queues.force-activate',
                    $configuration,
                ),
            )
            ->assertForbidden();
    }

    public function test_force_activation_permission_is_independent(): void
    {
        $configuration = $this->configuration();

        $user = $this->user([
            'admin.settings.queues.force_activate',
        ]);

        $this->actingAs($user)
            ->post(
                route(
                    'admin.system.queues.activate',
                    $configuration,
                ),
            )
            ->assertForbidden();
    }

    private function configuration(): QueueDriverConfiguration
    {
        return QueueDriverConfiguration::query()->create([
            'name' => 'Activation target',
            'driver' => QueueDriverType::Sync,
            'infrastructure_connection_id' => null,
            'configuration' => [],
            'is_enabled' => true,
        ]);
    }

    private function user(
        array $permissionKeys,
    ): User {
        $user = User::factory()->create();

        $group = PermissionGroup::query()->create([
            'key' => 'queue-activation-'.$user->id,
            'label' => 'Queue activation',
            'panel' => 'admin',
            'type' => 'agent',
            'sort_order' => 1,
        ]);

        $role = Role::query()->create([
            'name' => 'queue-activation-'.$user->id,
            'label' => 'Queue activation',
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

        $role->permissions()->sync(
            $permissionIds,
        );

        $user->roles()->attach(
            $role,
        );

        return $user;
    }

    public function test_deployment_activation_routes_use_separate_permissions(): void
    {
        $activate = Route::getRoutes()
            ->getByName('admin.system.queues.activate-deployment');

        $forceActivate = Route::getRoutes()
            ->getByName('admin.system.queues.force-activate-deployment');

        $this->assertNotNull($activate);
        $this->assertNotNull($forceActivate);

        $this->assertContains(
            'permission:admin.settings.queues.activate',
            $activate->gatherMiddleware(),
        );

        $this->assertContains(
            'permission:admin.settings.queues.force_activate',
            $forceActivate->gatherMiddleware(),
        );
    }
}

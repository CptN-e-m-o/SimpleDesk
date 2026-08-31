<?php

namespace Tests\Feature\Admin\Manage;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use Database\Seeders\Permissions\Agent\AdminPanel\PermissionAdminManageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagePermissionUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_permissions_are_expanded_for_custom_roles_before_removal(): void
    {
        $group = PermissionGroup::query()->create(['key' => 'legacy-manage', 'label' => 'Legacy Manage', 'panel' => 'admin', 'type' => 'agent', 'sort_order' => 1]);
        $legacyPriority = $this->permission($group, 'admin.manage.manage_priority');
        $legacyTicketTypes = $this->permission($group, 'admin.manage.manage_ticket_types');
        $unrelated = $this->permission($group, 'custom.permission.keep');
        $role = Role::query()->create(['name' => 'custom-manager', 'label' => 'Custom Manager', 'type' => 'user']);
        $role->permissions()->attach([$legacyPriority->id, $legacyTicketTypes->id, $unrelated->id]);

        $this->seed(PermissionAdminManageSeeder::class);

        $this->assertDatabaseMissing('permissions', ['key' => 'admin.manage.manage_priority']);
        $this->assertDatabaseMissing('permissions', ['key' => 'admin.manage.manage_ticket_types']);
        $this->assertDatabaseHas('permissions', ['key' => 'custom.permission.keep']);
        $this->assertEqualsCanonicalizing([
            'admin.manage.priorities.view',
            'admin.manage.priorities.create',
            'admin.manage.priorities.update',
            'admin.manage.priorities.archive',
            'admin.manage.ticket_types.view',
            'admin.manage.ticket_types.create',
            'admin.manage.ticket_types.update',
            'admin.manage.ticket_types.archive',
            'custom.permission.keep',
        ], $role->permissions()->pluck('key')->all());
    }

    private function permission(PermissionGroup $group, string $key): Permission
    {
        return Permission::query()->create(['permission_group_id' => $group->id, 'key' => $key, 'label' => $key, 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 1]);
    }
}

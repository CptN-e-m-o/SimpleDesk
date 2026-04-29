<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Permission;
use App\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Agent roles
        |--------------------------------------------------------------------------
        */

        $this->syncByQuery(
            'super-admin',
            Permission::query()->where('type', 'agent')
        );

        // Пока костыльно даём admin почти всё агентское.
        // Потом, когда полностью добьём agent/admin permissions, тут можно будет ограничить.
        $this->syncByQuery(
            'admin',
            Permission::query()->where('type', 'agent')
        );

        // Обычный agent не должен получать admin.* permissions.
        $this->syncByKeys('agent', [
            'agent.tickets.create',

            'agent.tickets.respond',
            'agent.tickets.reply',
            'agent.tickets.internal_notes.add',

            'agent.tickets.forward',
            'agent.tickets.approval_workflow.apply',

            'agent.tickets.properties.edit',
            'agent.tickets.properties.edit_specific',

            'agent.tickets.export',
            'agent.tickets.approval_pending.view',

            'agent.tickets.time_tracks.edit',
            'agent.tickets.time_tracks.edit_own',

            'agent.tickets.time_tracks.delete',
            'agent.tickets.time_tracks.delete_own',
        ]);

        /*
        |--------------------------------------------------------------------------
        | User roles
        |--------------------------------------------------------------------------
        */

        $this->syncByKeys('user', [
            'tickets.create',

            'tickets.respond',

            'tickets.visibility',
            'tickets.visibility.requester',
        ]);

        $this->syncByKeys('organization-user', [
            'tickets.create',
            'tickets.create_with_organization_assets',

            'tickets.respond',

            'tickets.visibility',
            'tickets.visibility.organization',
        ]);

        $this->syncByKeys('collaborators', [
            'tickets.collaborator.view',
        ]);

        $this->syncByKeys('agent-collaborators', [
            'tickets.collaborator.view',
        ]);
    }

    private function syncByKeys(string $roleName, array $permissionKeys): void
    {
        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            return;
        }

        $permissionIds = Permission::whereIn('key', $permissionKeys)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
    }

    private function syncByQuery(string $roleName, $query): void
    {
        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            return;
        }

        $permissionIds = $query
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['name' => 'super_admin'],
            [
                'label' => 'Super Admin',
                'description' => 'The Super Admin possesses the highest level of privileges within the system, enabling unrestricted access to all features and actions. It is mandatory to have at least one Super Admin account configured in the system.',
                'type' => 'agent',
                'is_system' => true,
                'is_default' => false,
            ]
        );

        Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'label' => 'Admin',
                'description' => 'The Admin role has limited access compared to the Super Admin. Admins can view and manage tickets specific to their linked departments and have permissions to access only selected modules related to ticket management. Additionally, they possess most of the permissions for all installed and enabled plugins.',
                'type' => 'agent',
                'is_system' => true,
                'is_default' => false,
            ]
        );

        Role::updateOrCreate(
            ['name' => 'agent'],
            [
                'label' => 'Agent',
                'description' => 'The Agent role has create and edit permissions across most modules in the system. Agents can access and view only the tickets assigned to them from the Agent Panel. While they have create and edit privileges for most modules, they do not have any permissions within the Admin Panel.',
                'type' => 'agent',
                'is_system' => true,
                'is_default' => true,
            ]
        );

        Role::updateOrCreate(
            ['name' => 'user'],
            [
                'label' => 'User',
                'description' => 'The User role applies exclusively to contacts accessing the Client Panel. Their permissions determine the actions they can perform within the Client Panel, restricting access solely to client-side functionalities.',
                'type' => 'user',
                'is_system' => true,
                'is_default' => true,
            ]
        );

        Role::updateOrCreate(
            ['name' => 'agent-collaborators'],
            [
                'label' => 'Agent Collaborators',
                'description' => 'The Agent Collaborator role is primarily intended for agents who need access to tickets where they are added as CCs, regardless of their linked departments or teams. These tickets can be viewed only through the Client Panel.',
                'type' => 'agent',
                'is_system' => false,
                'is_default' => false,
            ]
        );

        Role::updateOrCreate(
            ['name' => 'collaborators'],
            [
                'label' => 'Collaborators',
                'description' => 'The User Collaborator role allows users to access all tickets where they are added as CCs, irrespective of the organization they belong to.',
                'type' => 'user',
                'is_system' => false,
                'is_default' => false,
            ]
        );

        Role::updateOrCreate(
            ['name' => 'organization-user'],
            [
                'label' => 'Organization User',
                'description' => 'The Organization User role functions similarly to the User role, allowing users to view all tickets associated with the organization to which they belong.',
                'type' => 'user',
                'is_system' => false,
                'is_default' => false,
            ]
        );
    }
}

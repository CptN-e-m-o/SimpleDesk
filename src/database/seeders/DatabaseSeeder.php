<?php

namespace Database\Seeders;

use Database\Seeders\Permissions\Agent\AdminPanel\PermissionAdminMailSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,

            PermissionSeeder::class,
            RolePermissionSeeder::class,
            PermissionAdminMailSeeder::class,

            SuperAdminUserSeeder::class,
            AdminUserSeeder::class,
            AgentUserSeeder::class,
            RegularUserSeeder::class,

            DepartmentStatusSeeder::class,
            DepartmentSeeder::class,
            TeamSeeder::class,
            TeamMemberSeeder::class,
            WorkScheduleSeeder::class,
            AgentStatusSeeder::class,
        ]);
    }
}

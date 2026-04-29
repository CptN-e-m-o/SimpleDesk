<?php

namespace Database\Seeders;
use Database\Seeders\Permissions\User\PermissionUserBillingSeeder;
use Database\Seeders\Permissions\User\PermissionUserTicketsSeeder;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionUserBillingSeeder::class,
            PermissionUserTicketsSeeder::class,

            // Потом сюда докинем:
            // PermissionAdminGeneralSeeder::class,
            // PermissionAdminStaffSeeder::class,
            // PermissionAdminManageSeeder::class,
            // PermissionAgentTicketsSeeder::class,
        ]);
    }
}

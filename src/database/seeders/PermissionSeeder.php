<?php

namespace Database\Seeders;

use Database\Seeders\Permissions\Agent\AdminPanel\PermissionAdminAddOnsSeeder;
use Database\Seeders\Permissions\Agent\AdminPanel\PermissionAdminGeneralSeeder;
use Database\Seeders\Permissions\Agent\AdminPanel\PermissionAdminManageSeeder;
use Database\Seeders\Permissions\Agent\AdminPanel\PermissionAdminServiceDeskSeeder;
use Database\Seeders\Permissions\Agent\AdminPanel\PermissionAdminSettingsSeeder;
use Database\Seeders\Permissions\Agent\AdminPanel\PermissionAdminStaffSeeder;
use Database\Seeders\Permissions\Agent\AdminPanel\PermissionAdminTicketsSeeder;
use Database\Seeders\Permissions\Agent\AgentPanel\PermissionAgentAssetsSeeder;
use Database\Seeders\Permissions\Agent\AgentPanel\PermissionAgentChangesSeeder;
use Database\Seeders\Permissions\Agent\AgentPanel\PermissionAgentContactsSeeder;
use Database\Seeders\Permissions\Agent\AgentPanel\PermissionAgentContractsSeeder;
use Database\Seeders\Permissions\Agent\AgentPanel\PermissionAgentProblemsSeeder;
use Database\Seeders\Permissions\Agent\AgentPanel\PermissionAgentReleasesSeeder;
use Database\Seeders\Permissions\Agent\AgentPanel\PermissionAgentReportsSeeder;
use Database\Seeders\Permissions\Agent\AgentPanel\PermissionAgentSoftwareLicensesSeeder;
use Database\Seeders\Permissions\Agent\AgentPanel\PermissionAgentTicketsSeeder;
use Database\Seeders\Permissions\Agent\AgentPanel\PermissionAgentToolsSeeder;
use Database\Seeders\Permissions\Agent\ClientPanel\PermissionClientBillingSeeder;
use Database\Seeders\Permissions\Agent\ClientPanel\PermissionClientTicketsSeeder;
use Database\Seeders\Permissions\Agent\GeneralPanel\PermissionGeneralNetworkDiscoverySeeder;
use Database\Seeders\Permissions\Agent\GeneralPanel\PermissionGeneralNotificationsSeeder;
use Database\Seeders\Permissions\Agent\GeneralPanel\PermissionGeneralSecuritySeeder;
use Database\Seeders\Permissions\Agent\GeneralPanel\PermissionGeneralVisibilitySeeder;
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
            PermissionAdminGeneralSeeder::class,
            PermissionAdminStaffSeeder::class,
            PermissionAdminManageSeeder::class,
            PermissionAdminTicketsSeeder::class,
            PermissionAdminSettingsSeeder::class,
            PermissionAdminAddOnsSeeder::class,
            PermissionAdminServiceDeskSeeder::class,
            PermissionAgentTicketsSeeder::class,
            PermissionAgentContactsSeeder::class,
            PermissionAgentToolsSeeder::class,
            PermissionAgentReportsSeeder::class,
            PermissionAgentProblemsSeeder::class,
            PermissionAgentChangesSeeder::class,
            PermissionAgentReleasesSeeder::class,
            PermissionAgentAssetsSeeder::class,
            PermissionAgentContractsSeeder::class,
            PermissionAgentSoftwareLicensesSeeder::class,
            PermissionClientTicketsSeeder::class,
            PermissionClientBillingSeeder::class,
            PermissionGeneralNotificationsSeeder::class,
            PermissionGeneralVisibilitySeeder::class,
            PermissionGeneralSecuritySeeder::class,
            PermissionGeneralNetworkDiscoverySeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Admin\Department;
use App\Models\Admin\Team;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->firstOrFail();

        $defaultDepartment = Department::where('is_default', true)->first();
        $defaultTeam = Team::first();

        $admin = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'username' => 'administrator',

                'first_name' => 'System',
                'last_name' => 'Administrator',

                'location' => 'Berlin',

                'phone_country_iso2' => 'DE',
                'phone_country_code' => '+49',
                'phone_number' => '30123456',
                'phone_ext' => '001',

                'mobile_country_iso2' => 'DE',
                'mobile_country_code' => '+49',
                'mobile_number' => '15123456789',

                'timezone' => 'Europe/Berlin',

                'signature' => <<<HTML
<p>
Best regards,<br>
System Administrator<br>
SimpleDesk
</p>
HTML,

                'email_verified_at' => now(),

                'password' => Hash::make('password'),

                'is_active' => true,
            ]
        );

        // Roles
        $admin->roles()->sync([
            $superAdminRole->id,
        ]);

        // Departments
        if ($defaultDepartment) {
            $admin->departments()->syncWithoutDetaching([
                $defaultDepartment->id => [
                    'is_manager' => true,
                ],
            ]);
        }

        // Teams
        if ($defaultTeam) {
            $admin->teams()->syncWithoutDetaching([
                $defaultTeam->id => [
                    'is_lead' => true,
                ],
            ]);
        }
    }
}

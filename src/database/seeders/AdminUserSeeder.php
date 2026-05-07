<?php

namespace Database\Seeders;

use App\Models\Admin\Department;
use App\Models\Admin\Team;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->firstOrFail();

        $defaultDepartment = Department::where('is_default', true)->first();
        $defaultTeam = Team::first();

        $admins = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'username' => 'admin1',
                'email' => 'admin1@example.com',
            ],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'username' => 'admin2',
                'email' => 'admin2@example.com',
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Brown',
                'username' => 'admin3',
                'email' => 'admin3@example.com',
            ],
            [
                'first_name' => 'Emily',
                'last_name' => 'Johnson',
                'username' => 'admin4',
                'email' => 'admin4@example.com',
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Wilson',
                'username' => 'admin5',
                'email' => 'admin5@example.com',
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Taylor',
                'username' => 'admin6',
                'email' => 'admin6@example.com',
            ],
        ];

        foreach ($admins as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'username' => $data['username'],

                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],

                    'location' => 'Berlin',

                    'phone_country_iso2' => 'DE',
                    'phone_country_code' => '+49',
                    'phone_number' => '30123456',
                    'phone_ext' => '100',

                    'mobile_country_iso2' => 'DE',
                    'mobile_country_code' => '+49',
                    'mobile_number' => '15123456789',

                    'timezone' => 'Europe/Berlin',

                    'signature' => <<<HTML
<p>
Best regards,<br>
{$data['first_name']} {$data['last_name']}<br>
SimpleDesk Administrator
</p>
HTML,

                    'email_verified_at' => now(),

                    'password' => Hash::make('password'),

                    'is_active' => true,
                ]
            );

            // Roles
            $user->roles()->syncWithoutDetaching([
                $adminRole->id,
            ]);

            // Departments
            if ($defaultDepartment) {
                $user->departments()->syncWithoutDetaching([
                    $defaultDepartment->id => [
                        'is_manager' => true,
                    ],
                ]);
            }

            // Teams
            if ($defaultTeam) {
                $user->teams()->syncWithoutDetaching([
                    $defaultTeam->id => [
                        'is_lead' => true,
                    ],
                ]);
            }
        }
    }
}

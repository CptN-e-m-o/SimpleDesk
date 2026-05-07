<?php

namespace Database\Seeders;

use App\Models\Admin\Department;
use App\Models\Admin\Team;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AgentUserSeeder extends Seeder
{
    public function run(): void
    {
        $agentRole = Role::where('name', 'agent')->firstOrFail();

        $defaultDepartment = Department::where('is_default', true)->first();
        $defaultTeam = Team::first();

        $agents = [
            [
                'first_name' => 'Alex',
                'last_name' => 'Miller',
                'username' => 'agent1',
                'email' => 'agent1@example.com',
            ],
            [
                'first_name' => 'Olivia',
                'last_name' => 'Davis',
                'username' => 'agent2',
                'email' => 'agent2@example.com',
            ],
            [
                'first_name' => 'Daniel',
                'last_name' => 'Moore',
                'username' => 'agent3',
                'email' => 'agent3@example.com',
            ],
            [
                'first_name' => 'Sophia',
                'last_name' => 'Anderson',
                'username' => 'agent4',
                'email' => 'agent4@example.com',
            ],
            [
                'first_name' => 'James',
                'last_name' => 'Thomas',
                'username' => 'agent5',
                'email' => 'agent5@example.com',
            ],
            [
                'first_name' => 'Emma',
                'last_name' => 'Jackson',
                'username' => 'agent6',
                'email' => 'agent6@example.com',
            ],
        ];

        foreach ($agents as $data) {
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
                    'phone_ext' => null,

                    'mobile_country_iso2' => 'DE',
                    'mobile_country_code' => '+49',
                    'mobile_number' => '15123456789',

                    'timezone' => 'Europe/Berlin',

                    'signature' => <<<HTML
<p>
Best regards,<br>
{$data['first_name']} {$data['last_name']}<br>
Customer Support Agent<br>
SimpleDesk
</p>
HTML,

                    'email_verified_at' => now(),

                    'password' => Hash::make('password'),

                    'is_active' => true,
                ]
            );

            // Roles
            $user->roles()->syncWithoutDetaching([
                $agentRole->id,
            ]);

            // Departments
            if ($defaultDepartment) {
                $user->departments()->syncWithoutDetaching([
                    $defaultDepartment->id => [
                        'is_manager' => false,
                    ],
                ]);
            }

            // Teams
            if ($defaultTeam) {
                $user->teams()->syncWithoutDetaching([
                    $defaultTeam->id => [
                        'is_lead' => false,
                    ],
                ]);
            }
        }
    }
}

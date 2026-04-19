<?php

namespace Database\Seeders;

use App\Models\Admin\Team;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $agentRole = Role::where('name', 'agent')->firstOrFail();
        $userRole = Role::where('name', 'user')->firstOrFail();

        $admin = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Administrator',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $agents = collect();

        for ($i = 1; $i <= 6; $i++) {
            $agent = User::updateOrCreate(
                ['email' => "agent{$i}@example.com"],
                [
                    'name' => "Agent {$i}",
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );

            $agent->roles()->syncWithoutDetaching([$agentRole->id]);
            $agents->push($agent);
        }

        $users = User::factory(20)->create();

        foreach ($users as $user) {
            $user->roles()->syncWithoutDetaching([$userRole->id]);
        }

        $teamsData = [
            [
                'name' => 'Support Team',
                'departments' => ['Support', 'Helpdesk'],
                'is_active' => true,
                'admin_notes' => 'Основная команда первой линии поддержки.',
            ],
            [
                'name' => 'Technical Team',
                'departments' => ['IT', 'Infrastructure'],
                'is_active' => true,
                'admin_notes' => 'Техническая команда для сложных обращений.',
            ],
            [
                'name' => 'Sales Team',
                'departments' => ['Sales'],
                'is_active' => true,
                'admin_notes' => 'Команда для коммерческих запросов.',
            ],
            [
                'name' => 'HR Team',
                'departments' => ['HR'],
                'is_active' => true,
                'admin_notes' => 'Команда для кадровых вопросов.',
            ],
        ];

        $teamAssignableUsers = $agents->prepend($admin);

        foreach ($teamsData as $teamData) {
            $team = Team::updateOrCreate(
                ['name' => $teamData['name']],
                [
                    'departments' => $teamData['departments'],
                    'is_active' => $teamData['is_active'],
                    'admin_notes' => $teamData['admin_notes'],
                ]
            );

            $members = $teamAssignableUsers->shuffle()->take(rand(2, 4));

            $syncData = [];

            foreach ($members as $index => $member) {
                $syncData[$member->id] = [
                    'is_lead' => $index === 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $team->members()->sync($syncData);
        }
    }
}

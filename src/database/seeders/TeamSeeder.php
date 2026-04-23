<?php

namespace Database\Seeders;

use App\Models\Admin\Department;
use App\Models\Admin\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teamsData = [
            [
                'name' => 'Support Team',
                'slug' => 'support-team',
                'is_active' => true,
                'admin_notes' => 'Основная команда первой линии поддержки.',
                'departments' => ['support', 'helpdesk'],
            ],
            [
                'name' => 'Technical Team',
                'slug' => 'technical-team',
                'is_active' => true,
                'admin_notes' => 'Техническая команда для сложных обращений.',
                'departments' => ['it', 'infrastructure'],
            ],
            [
                'name' => 'Sales Team',
                'slug' => 'sales-team',
                'is_active' => true,
                'admin_notes' => 'Команда для коммерческих запросов.',
                'departments' => ['sales'],
            ],
            [
                'name' => 'HR Team',
                'slug' => 'hr-team',
                'is_active' => true,
                'admin_notes' => 'Команда для кадровых вопросов.',
                'departments' => ['hr'],
            ],
        ];

        foreach ($teamsData as $teamData) {
            $team = Team::updateOrCreate(
                ['slug' => $teamData['slug']],
                [
                    'name' => $teamData['name'],
                    'slug' => $teamData['slug'],
                    'is_active' => $teamData['is_active'],
                    'admin_notes' => $teamData['admin_notes'],
                ]
            );

            $departmentIds = Department::query()
                ->whereIn('slug', $teamData['departments'])
                ->pluck('id')
                ->all();

            $team->departments()->sync($departmentIds);
        }
    }
}

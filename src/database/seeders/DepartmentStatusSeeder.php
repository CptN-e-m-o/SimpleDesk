<?php

namespace Database\Seeders;

use App\Models\Admin\DepartmentStatus;
use Illuminate\Database\Seeder;

class DepartmentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Active',
                'code' => 'active',
                'color' => 'green',
                'icon' => 'check-circle',
            ],
            [
                'name' => 'Inactive',
                'code' => 'inactive',
                'color' => 'gray',
                'icon' => 'pause-circle',
            ],
            [
                'name' => 'Archived',
                'code' => 'archived',
                'color' => 'amber',
                'icon' => 'archive',
            ],
        ];

        foreach ($statuses as $status) {
            DepartmentStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );
        }
    }
}

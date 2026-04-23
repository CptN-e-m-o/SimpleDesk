<?php

namespace Database\Seeders;

use App\Models\Admin\Department;
use App\Models\Admin\DepartmentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $activeStatusId = DepartmentStatus::where('code', 'active')->value('id');

        $departments = [
            [
                'name' => 'Support',
                'type' => 'public',
                'business_hours' => 'Standard business hours',
                'outgoing_email' => 'support@example.com',
                'signature' => '<p>Best regards,<br>Support Team</p>',
                'is_default' => true,
            ],
            [
                'name' => 'Helpdesk',
                'type' => 'public',
                'business_hours' => '24/7',
                'outgoing_email' => 'helpdesk@example.com',
                'signature' => '<p>Best regards,<br>Helpdesk Team</p>',
                'is_default' => false,
            ],
            [
                'name' => 'IT',
                'type' => 'private',
                'business_hours' => 'Extended hours',
                'outgoing_email' => 'it@example.com',
                'signature' => '<p>Best regards,<br>IT Department</p>',
                'is_default' => false,
            ],
            [
                'name' => 'Infrastructure',
                'type' => 'private',
                'business_hours' => 'Extended hours',
                'outgoing_email' => 'infra@example.com',
                'signature' => '<p>Best regards,<br>Infrastructure Team</p>',
                'is_default' => false,
            ],
            [
                'name' => 'Sales',
                'type' => 'public',
                'business_hours' => 'Weekdays 09:00–18:00',
                'outgoing_email' => 'sales@example.com',
                'signature' => '<p>Best regards,<br>Sales Team</p>',
                'is_default' => false,
            ],
            [
                'name' => 'HR',
                'type' => 'private',
                'business_hours' => 'Weekdays 09:00–18:00',
                'outgoing_email' => 'hr@example.com',
                'signature' => '<p>Best regards,<br>HR Department</p>',
                'is_default' => false,
            ],
        ];

        foreach ($departments as $departmentData) {
            Department::updateOrCreate(
                ['slug' => Str::slug($departmentData['name'])],
                [
                    'name' => $departmentData['name'],
                    'slug' => Str::slug($departmentData['name']),
                    'type' => $departmentData['type'],
                    'business_hours' => $departmentData['business_hours'],
                    'outgoing_email' => $departmentData['outgoing_email'],
                    'department_status_id' => $activeStatusId,
                    'signature' => $departmentData['signature'],
                    'is_default' => $departmentData['is_default'],
                ]
            );
        }
    }
}

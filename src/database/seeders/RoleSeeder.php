<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'label' => 'Администратор',
                'description' => 'Полный доступ к системе',
            ]
        );

        Role::updateOrCreate(
            ['name' => 'agent'],
            [
                'label' => 'Агент',
                'description' => 'Обрабатывает заявки пользователей',
            ]
        );

        Role::updateOrCreate(
            ['name' => 'user'],
            [
                'label' => 'Пользователь',
                'description' => 'Создаёт заявки и следит за своими обращениями',
            ]
        );
    }
}

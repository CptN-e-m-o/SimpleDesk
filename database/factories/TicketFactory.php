<?php

namespace Database\Factories;

use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(3),

            // Берем случайного существующего пользователя
            'user_id' => User::inRandomOrder()->first()->id,

            // Назначаем агента в 75% случаев, иначе оставляем NULL
            'assigned_agent_id' => fake()->boolean(75) ? User::inRandomOrder()->first()->id : null,

            // Берем случайный статус из таблицы statuses
            'status_id' => Status::inRandomOrder()->first()->id,

            // Берем случайный приоритет из таблицы priorities
            'priority_id' => Priority::inRandomOrder()->first()->id,
        ];
    }
}

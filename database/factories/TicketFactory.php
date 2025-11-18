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

            'user_id' => User::inRandomOrder()->first()->id,

            'assigned_agent_id' => fake()->boolean(75) ? User::inRandomOrder()->first()->id : null,

            'status_id' => Status::inRandomOrder()->first()->id,

            'priority_id' => Priority::inRandomOrder()->first()->id,
        ];
    }
}

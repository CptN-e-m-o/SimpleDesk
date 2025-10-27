<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Models\Priority;         // <-- Импортируем модель
use Illuminate\Database\Seeder;    // <-- Импортируем Enum

class PrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (TicketPriority::cases() as $priority) {
            Priority::firstOrCreate([
                'id' => $priority->value,
                'name' => $priority->label(),
            ]);
        }
    }
}

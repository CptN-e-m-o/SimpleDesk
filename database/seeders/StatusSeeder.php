<?php

namespace Database\Seeders;

use App\Enums\TicketStatus;
use App\Models\Status;         // <-- Импортируем модель
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TicketStatus::cases() as $status) {
            Status::firstOrCreate([
                'id' => $status->value,
                'name' => $status->label(),
            ]);
        }
    }
}

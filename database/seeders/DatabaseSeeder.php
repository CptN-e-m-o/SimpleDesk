<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            StatusSeeder::class,
            PrioritySeeder::class,
            UserSeeder::class,
        ]);

        $this->call(TicketSeeder::class);
    }
}

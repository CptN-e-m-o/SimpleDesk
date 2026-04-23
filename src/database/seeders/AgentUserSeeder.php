<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AgentUserSeeder extends Seeder
{
    public function run(): void
    {
        $agentRole = Role::where('name', 'agent')->firstOrFail();

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
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Admin\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamMemberSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@test.com')->first();

        $agents = User::query()
            ->where('email', 'like', 'agent%@example.com')
            ->get();

        $teamAssignableUsers = $agents->when(
            $admin,
            fn ($collection) => $collection->prepend($admin)
        );

        if ($teamAssignableUsers->isEmpty()) {
            return;
        }

        $teams = Team::all();

        foreach ($teams as $team) {
            $members = $teamAssignableUsers
                ->shuffle()
                ->take(min(rand(2, 4), $teamAssignableUsers->count()));

            $syncData = [];

            foreach ($members->values() as $index => $member) {
                $syncData[$member->id] = [
                    'is_lead' => $index === 0,
                ];
            }

            $team->members()->sync($syncData);
        }
    }
}

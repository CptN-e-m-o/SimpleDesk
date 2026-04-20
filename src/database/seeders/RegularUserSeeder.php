<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RegularUserSeeder extends Seeder
{
    public function run(): void
    {
        $userRole = Role::where('name', 'user')->firstOrFail();

        $users = User::factory(20)->create();

        foreach ($users as $user) {
            $user->roles()->syncWithoutDetaching([$userRole->id]);
        }
    }
}

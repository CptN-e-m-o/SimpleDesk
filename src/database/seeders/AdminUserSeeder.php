<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'super-admin')->firstOrFail();

        $admin = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Administrator',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        $admin->roles()->sync([$adminRole->id]);
    }
}

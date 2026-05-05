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
        $adminRole = Role::where('name', 'admin')->firstOrFail();

        for ($i = 1; $i <= 6; $i++) {
            $admin = User::updateOrCreate(
                ['email' => "admin{$i}@example.com"],
                [
                    'name' => "Admin {$i}",
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );

            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}

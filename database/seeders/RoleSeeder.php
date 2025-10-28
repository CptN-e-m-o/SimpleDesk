<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (UserRole::cases() as $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $role->value],
                ['name' => $role->name]
            );
        }
    }
}

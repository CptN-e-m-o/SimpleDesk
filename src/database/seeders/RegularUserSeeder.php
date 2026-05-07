<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RegularUserSeeder extends Seeder
{
    public function run(): void
    {
        $userRole = Role::where('name', 'user')->firstOrFail();

        $users = [
            [
                'first_name' => 'Liam',
                'last_name' => 'Walker',
                'username' => 'user1',
                'email' => 'user1@example.com',
            ],
            [
                'first_name' => 'Noah',
                'last_name' => 'Hall',
                'username' => 'user2',
                'email' => 'user2@example.com',
            ],
            [
                'first_name' => 'Mia',
                'last_name' => 'Allen',
                'username' => 'user3',
                'email' => 'user3@example.com',
            ],
            [
                'first_name' => 'Charlotte',
                'last_name' => 'Young',
                'username' => 'user4',
                'email' => 'user4@example.com',
            ],
            [
                'first_name' => 'Ethan',
                'last_name' => 'Hernandez',
                'username' => 'user5',
                'email' => 'user5@example.com',
            ],
            [
                'first_name' => 'Amelia',
                'last_name' => 'King',
                'username' => 'user6',
                'email' => 'user6@example.com',
            ],
            [
                'first_name' => 'Benjamin',
                'last_name' => 'Wright',
                'username' => 'user7',
                'email' => 'user7@example.com',
            ],
            [
                'first_name' => 'Harper',
                'last_name' => 'Lopez',
                'username' => 'user8',
                'email' => 'user8@example.com',
            ],
            [
                'first_name' => 'Lucas',
                'last_name' => 'Hill',
                'username' => 'user9',
                'email' => 'user9@example.com',
            ],
            [
                'first_name' => 'Evelyn',
                'last_name' => 'Scott',
                'username' => 'user10',
                'email' => 'user10@example.com',
            ],
            [
                'first_name' => 'Henry',
                'last_name' => 'Green',
                'username' => 'user11',
                'email' => 'user11@example.com',
            ],
            [
                'first_name' => 'Abigail',
                'last_name' => 'Adams',
                'username' => 'user12',
                'email' => 'user12@example.com',
            ],
            [
                'first_name' => 'Alexander',
                'last_name' => 'Baker',
                'username' => 'user13',
                'email' => 'user13@example.com',
            ],
            [
                'first_name' => 'Ella',
                'last_name' => 'Nelson',
                'username' => 'user14',
                'email' => 'user14@example.com',
            ],
            [
                'first_name' => 'Matthew',
                'last_name' => 'Carter',
                'username' => 'user15',
                'email' => 'user15@example.com',
            ],
            [
                'first_name' => 'Scarlett',
                'last_name' => 'Mitchell',
                'username' => 'user16',
                'email' => 'user16@example.com',
            ],
            [
                'first_name' => 'Joseph',
                'last_name' => 'Perez',
                'username' => 'user17',
                'email' => 'user17@example.com',
            ],
            [
                'first_name' => 'Grace',
                'last_name' => 'Roberts',
                'username' => 'user18',
                'email' => 'user18@example.com',
            ],
            [
                'first_name' => 'Samuel',
                'last_name' => 'Turner',
                'username' => 'user19',
                'email' => 'user19@example.com',
            ],
            [
                'first_name' => 'Chloe',
                'last_name' => 'Phillips',
                'username' => 'user20',
                'email' => 'user20@example.com',
            ],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'username' => $data['username'],

                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],

                    'location' => 'Berlin',

                    'phone_country_iso2' => 'DE',
                    'phone_country_code' => '+49',
                    'phone_number' => '30123456',
                    'phone_ext' => null,

                    'mobile_country_iso2' => 'DE',
                    'mobile_country_code' => '+49',
                    'mobile_number' => '15123456789',

                    'timezone' => 'Europe/Berlin',

                    'signature' => null,

                    'email_verified_at' => now(),

                    'password' => Hash::make('password'),

                    'is_active' => true,
                ]
            );

            $user->roles()->syncWithoutDetaching([
                $userRole->id,
            ]);
        }
    }
}

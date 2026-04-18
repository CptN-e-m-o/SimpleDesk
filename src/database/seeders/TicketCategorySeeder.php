<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Getting Started',
                'description' => 'Basic setup, onboarding, and first steps.',
                'sort_order' => 10,
            ],
            [
                'name' => 'Account & Access',
                'description' => 'Login, password reset, permissions, and account issues.',
                'sort_order' => 20,
            ],
            [
                'name' => 'Billing',
                'description' => 'Invoices, payments, subscriptions, and pricing.',
                'sort_order' => 30,
            ],
            [
                'name' => 'Technical Issues',
                'description' => 'Bugs, crashes, unexpected behavior, and troubleshooting.',
                'sort_order' => 40,
            ],
            [
                'name' => 'Feature Request',
                'description' => 'Ideas and suggestions for product improvements.',
                'sort_order' => 50,
            ],
        ];

        foreach ($categories as $category) {
            TicketCategory::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                ]
            );
        }
    }
}

<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'ticket_number' => 'SD-'.fake()->unique()->numerify('######'),
            'requester_id' => User::factory(),
            'category_id' => TicketCategory::factory(),
            'assignee_id' => null,
            'subject' => fake()->sentence(),
            'priority' => fake()->randomElement(Ticket::priorities()),
            'status' => fake()->randomElement([
                Ticket::STATUS_OPEN,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_RESOLVED,
            ]),
            'source' => Ticket::SOURCE_PORTAL,
            'service' => fake()->optional()->randomElement([
                'Dashboard',
                'Billing',
                'Authentication',
                'Knowledge Base',
            ]),
            'description' => fake()->paragraphs(3, true),
            'last_reply_at' => now(),
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }
}

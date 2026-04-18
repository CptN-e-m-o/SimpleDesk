<?php

namespace App\Services\Tickets\User;

use App\Http\Resources\Tickets\User\TicketIndexResource;
use App\Models\Ticket;
use App\Repositories\Tickets\User\TicketRepository;

class TicketService
{
    public function __construct(
        private readonly TicketRepository $ticketRepository,
    ) {}

    public function getUserTicketsIndexData(int $userId, array $filters): array
    {
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? null;
        $priority = $filters['priority'] ?? null;

        $query = $this->ticketRepository->queryUserTickets($userId);

        $query = $this->ticketRepository->applyFilters($query, [
            'search' => $search,
            'status' => $status,
            'priority' => $priority,
        ]);

        $tickets = $this->ticketRepository->paginateForIndex($query);

        $tickets->through(
            fn (Ticket $ticket) => TicketIndexResource::make($ticket)->resolve()
        );

        return [
            'tickets' => $tickets,
            'filters' => [
                'search' => $search,
                'status' => $status ?? '',
                'priority' => $priority ?? '',
            ],
            'statusOptions' => Ticket::statusOptions(),
            'priorityOptions' => Ticket::priorityOptions(),
        ];
    }
}

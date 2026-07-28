<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use App\Models\User\User;

class TicketAccessService
{
    public function canView(
        User $user,
        Ticket $ticket
    ): bool {
        if ($ticket->requester_id === $user->id) {
            return true;
        }

        if (
            $user->isSuperAdmin()
            || $user->hasPermission(
                'agent.tickets.visibility.all'
            )
        ) {
            return true;
        }

        if (
            $user->hasPermission(
                'agent.tickets.visibility.assigned'
            )
            && $ticket->assignee_id === $user->id
        ) {
            return true;
        }

        if (
            $ticket->department_id !== null
            && $user->hasPermission(
                'agent.tickets.visibility.department'
            )
            && $user
                ->departments()
                ->whereKey($ticket->department_id)
                ->exists()
        ) {
            return true;
        }

        if (
            $ticket->department_id !== null
            && $user->hasPermission(
                'agent.tickets.visibility.team'
            )
            && $user
                ->teams()
                ->whereHas(
                    'departments',
                    fn ($query) => $query->whereKey(
                        $ticket->department_id
                    )
                )
                ->exists()
        ) {
            return true;
        }

        return false;
    }
}

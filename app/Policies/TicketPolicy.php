<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isAdminOrAgent() || $user->id === $ticket->user_id;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isAdminOrAgent()) {
            return true;
        }

        return $user->id === $ticket->user_id && $ticket->status->name === 'Открыта';
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdminOrAgent();
    }
}

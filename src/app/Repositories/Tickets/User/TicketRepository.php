<?php

namespace App\Repositories\Tickets\User;

use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TicketRepository
{
    public function queryUserTickets(int $userId): Builder
    {
        return Ticket::query()
            ->with(['category:id,name'])
            ->where('requester_id', $userId);
    }

    public function applyFilters(
        Builder $query,
        array $filters,
    ): Builder {
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? null;
        $priority = $filters['priority'] ?? null;

        return $query
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status, function (Builder $query) use ($status) {
                $query->where('status', $status);
            })
            ->when($priority, function (Builder $query) use ($priority) {
                $query->where('priority', $priority);
            });
    }

    public function paginateForIndex(Builder $query, int $perPage = 10): LengthAwarePaginator
    {
        return $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}

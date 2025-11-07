<?php

namespace App\Http\Controllers\Agent;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $baseQuery = Ticket::query();

        $counts = [
            'open' => (clone $baseQuery)->where('status_id', TicketStatus::Open->value)->count(),
            'assigned' => (clone $baseQuery)->where('assigned_agent_id', $user->id)->count(),
            'created' => Ticket::query()->where('user_id', $user->id)->count(),
            'unassigned' => (clone $baseQuery)->whereNull('assigned_agent_id')->count(),
            'overdue' => (clone $baseQuery)->where('status_id', TicketStatus::Overdue->value)->count(),
            'unanswered' => (clone $baseQuery)->where('status_id', TicketStatus::Unanswered->value)->count(),
            'pending_approvals' => (clone $baseQuery)->where('status_id', TicketStatus::Pending_Approval->value)->count(),
            'closed' => (clone $baseQuery)->where('status_id', TicketStatus::Closed->value)->count(),
            'spam' => (clone $baseQuery)->where('status_id', TicketStatus::Spam->value)->count(),
            'trash' => (clone $baseQuery)->onlyTrashed()->count(),
        ];

        return view('agent.dashboard.index', compact('counts'));
    }
}

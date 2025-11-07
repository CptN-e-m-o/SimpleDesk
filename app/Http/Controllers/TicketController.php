<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Models\Priority;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TicketController extends Controller
{
    const ELEMENTS_PER_PAGE = 20;

    public function index(string $category): View
    {
        $user = auth()->user();
        $query = Ticket::with('user', 'priority');
        $title = '';

        switch ($category) {
            case 'created':
                $query->where('user_id', $user->id);
                $title = __('lang.my_created_tickets');
                break;

            case 'assigned':
                if (! $user->isAdminOrAgent()) {
                    abort(404);
                }
                $query->where('assigned_agent_id', $user->id);
                $title = __('lang.tickets_assigned_to_me');
                break;

            case 'unassigned':
                if (! $user->isAdminOrAgent()) {
                    abort(404);
                }
                $query->whereNull('assigned_agent_id');
                $title = __('lang.unassigned_tickets');
                break;

            case 'my-pending-approvals':
                if (! $user->isAdminOrAgent()) {
                    abort(404);
                }
                $query->where('assigned_agent_id', $user->id)
                    ->where('status_id', TicketStatus::Pending_Approval->value);
                $title = __('lang.my_pending_approvals');
                break;

            case 'trash':
                $query->onlyTrashed();
                $title = __('lang.trash');
                break;

            default:
                $statusEnum = TicketStatus::fromName($category);
                if ($statusEnum) {
                    $query->where('status_id', $statusEnum->value);
                    $title = $statusEnum->label();
                } else {
                    abort(404);
                }
                break;
        }

        if (! $user->isAdminOrAgent()) {
            $query->where('user_id', $user->id);
        }

        $tickets = $query->latest()->paginate(self::ELEMENTS_PER_PAGE);

        return view('tickets.index', compact('tickets', 'title'));
    }

    public function create(): View
    {
        $this->authorize('create', Ticket::class);
        $currentUser = auth()->user();
        $assignees = [];
        if ($currentUser->isAdminOrAgent()) {
            $assignees = User::whereIn('role_id', [UserRole::Admin, UserRole::Agent])->orderBy('last_name')->get();
        }

        return view('tickets.create', [
            'users' => $assignees,
            'priorities' => Priority::all(),
            'currentUser' => $currentUser,
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $this->authorize('create', Ticket::class);
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        $validated['status_id'] = TicketStatus::Open->value;
        if (! auth()->user()->isAdminOrAgent()) {
            $validated['priority_id'] = TicketPriority::Low->value;
        }
        Ticket::create($validated);

        return redirect()->route('tickets.index')->with('success', __('lang.ticket_created_success'));
    }

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load(['replies.author', 'user', 'status', 'priority']);

        return view('tickets.show', compact('ticket'));
    }

    public function edit(Ticket $ticket): View
    {
        $this->authorize('update', $ticket);
        $currentUser = auth()->user();
        $assignees = [];
        $statuses = [];
        $priorities = [];
        if ($currentUser->isAdminOrAgent()) {
            $assignees = User::whereIn('role_id', [UserRole::Admin, UserRole::Agent])->orderBy('name')->get();
            $statuses = Status::all();
            $priorities = Priority::all();
        }

        return view('tickets.edit', [
            'ticket' => $ticket,
            'users' => $assignees,
            'statuses' => $statuses,
            'priorities' => $priorities,
            'currentUser' => $currentUser,
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);
        $ticket->update($request->validated());

        return redirect()->route('tickets.index')->with('success', __('ticket_updated_success', ['id' => $ticket->id]));
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $this->authorize('delete', $ticket);
        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', __('ticket_deleted_success', ['id' => $ticket->id]));
    }
}

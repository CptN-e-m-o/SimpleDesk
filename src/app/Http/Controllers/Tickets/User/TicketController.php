<?php

namespace App\Http\Controllers\Tickets\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreTicketRequest;
use App\Models\Ticket;
use App\Models\TicketCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $status = (string) ($validated['status'] ?? '');
        $priority = (string) ($validated['priority'] ?? '');

        $query = Ticket::query()
            ->with(['category:id,name'])
            ->where('requester_id', auth()->id());

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status !== '' && in_array($status, Ticket::statuses(), true)) {
            $query->where('status', $status);
        }

        if ($priority !== '' && in_array($priority, Ticket::priorities(), true)) {
            $query->where('priority', $priority);
        }

        $tickets = $query
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function (Ticket $ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'service' => $ticket->service,
                    'created_at' => $ticket->created_at?->toDateTimeString(),
                    'last_reply_at' => $ticket->last_reply_at?->toDateTimeString(),
                    'category' => $ticket->category
                        ? [
                            'id' => $ticket->category->id,
                            'name' => $ticket->category->name,
                        ]
                        : null,
                ];
            });

        return Inertia::render('Tickets/User/Index', [
            'tickets' => $tickets,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'priority' => $priority,
            ],
            'statusOptions' => [
                ['value' => '', 'label' => 'All statuses'],
                ['value' => Ticket::STATUS_OPEN, 'label' => 'Open'],
                ['value' => Ticket::STATUS_IN_PROGRESS, 'label' => 'In Progress'],
                ['value' => Ticket::STATUS_WAITING_FOR_CUSTOMER, 'label' => 'Waiting for Customer'],
                ['value' => Ticket::STATUS_RESOLVED, 'label' => 'Resolved'],
                ['value' => Ticket::STATUS_CLOSED, 'label' => 'Closed'],
            ],
            'priorityOptions' => [
                ['value' => '', 'label' => 'All priorities'],
                ['value' => Ticket::PRIORITY_LOW, 'label' => 'Low'],
                ['value' => Ticket::PRIORITY_MEDIUM, 'label' => 'Medium'],
                ['value' => Ticket::PRIORITY_HIGH, 'label' => 'High'],
                ['value' => Ticket::PRIORITY_URGENT, 'label' => 'Urgent'],
            ],
        ]);
    }

    public function create(): Response
    {
        $categories = TicketCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return Inertia::render('Tickets/User/Create', [
            'categories' => $categories,
            'priorityOptions' => [
                ['value' => Ticket::PRIORITY_LOW, 'label' => 'Low'],
                ['value' => Ticket::PRIORITY_MEDIUM, 'label' => 'Medium'],
                ['value' => Ticket::PRIORITY_HIGH, 'label' => 'High'],
                ['value' => Ticket::PRIORITY_URGENT, 'label' => 'Urgent'],
            ],
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $ticket = DB::transaction(function () use ($request, $validated) {
            $ticket = Ticket::create([
                'ticket_number' => $this->generateTicketNumber(),
                'requester_id' => $request->user()->id,
                'category_id' => $validated['category_id'],
                'subject' => $validated['subject'],
                'priority' => $validated['priority'],
                'status' => Ticket::STATUS_OPEN,
                'source' => Ticket::SOURCE_PORTAL,
                'service' => $validated['service'] ?? null,
                'description' => $validated['description'],
                'last_reply_at' => now(),
            ]);

            // Вложения можно подключить позже.
            // Например через media library или отдельную таблицу ticket_attachments.

            return $ticket;
        });

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Your ticket has been created successfully.');
    }

    public function show(Ticket $ticket): Response
    {
        abort_unless($ticket->requester_id === auth()->id(), 403);

        $ticket->load([
            'category:id,name',
            'requester:id,name,email',
            'assignee:id,name,email',
            'replies.user:id,name,email',
        ]);

        return Inertia::render('Tickets/User/Show', [
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'service' => $ticket->service,
                'source' => $ticket->source,
                'created_at' => $ticket->created_at?->toDateTimeString(),
                'updated_at' => $ticket->updated_at?->toDateTimeString(),
                'last_reply_at' => $ticket->last_reply_at?->toDateTimeString(),
                'resolved_at' => $ticket->resolved_at?->toDateTimeString(),
                'closed_at' => $ticket->closed_at?->toDateTimeString(),
                'category' => $ticket->category
                    ? [
                        'id' => $ticket->category->id,
                        'name' => $ticket->category->name,
                    ]
                    : null,
                'requester' => $ticket->requester
                    ? [
                        'id' => $ticket->requester->id,
                        'name' => $ticket->requester->name,
                        'email' => $ticket->requester->email,
                    ]
                    : null,
                'assignee' => $ticket->assignee
                    ? [
                        'id' => $ticket->assignee->id,
                        'name' => $ticket->assignee->name,
                        'email' => $ticket->assignee->email,
                    ]
                    : null,
                'replies' => $ticket->replies
                    ->sortBy('created_at')
                    ->values()
                    ->map(function ($reply) {
                        return [
                            'id' => $reply->id,
                            'message' => $reply->message,
                            'is_internal' => (bool) $reply->is_internal,
                            'created_at' => $reply->created_at?->toDateTimeString(),
                            'user' => $reply->user
                                ? [
                                    'id' => $reply->user->id,
                                    'name' => $reply->user->name,
                                    'email' => $reply->user->email,
                                ]
                                : null,
                        ];
                    }),
            ],
        ]);
    }

    protected function generateTicketNumber(): string
    {
        do {
            $ticketNumber = 'SD-' . now()->format('Y') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Ticket::query()->where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }
}

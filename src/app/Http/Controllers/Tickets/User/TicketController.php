<?php

namespace App\Http\Controllers\Tickets\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\User\TicketIndexRequest;
use App\Http\Requests\Tickets\User\TicketStoreRequest;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Services\Tickets\User\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function index(TicketIndexRequest $request): Response
    {
        return Inertia::render(
            'Tickets/User/Index',
            $this->ticketService->getUserTicketsIndexData(
                userId: auth()->id(),
                filters: $request->validated(),
            )
        );
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

    public function store(TicketStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $ticket = DB::transaction(function () use ($request, $validated) {
            return Ticket::create([
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
            'requester:id,first_name,last_name,email',
            'assignee:id,first_name,last_name,email',
            'replies.user:id,first_name,last_name,email',
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

                'category' => $ticket->category ? [
                    'id' => $ticket->category->id,
                    'name' => $ticket->category->name,
                ] : null,

                'requester' => $ticket->requester ? [
                    'id' => $ticket->requester->id,
                    'name' => $ticket->requester->name,
                    'email' => $ticket->requester->email,
                ] : null,

                'assignee' => $ticket->assignee ? [
                    'id' => $ticket->assignee->id,
                    'name' => $ticket->assignee->name,
                    'email' => $ticket->assignee->email,
                ] : null,

                'replies' => $ticket->replies
                    ->sortBy('created_at')
                    ->values()
                    ->map(fn ($reply) => [
                        'id' => $reply->id,
                        'message' => $reply->message,
                        'is_internal' => (bool) $reply->is_internal,
                        'created_at' => $reply->created_at?->toDateTimeString(),

                        'user' => $reply->user ? [
                            'id' => $reply->user->id,
                            'name' => $reply->user->name,
                            'email' => $reply->user->email,
                        ] : null,
                    ]),
            ],
        ]);
    }

    protected function generateTicketNumber(): string
    {
        do {
            $ticketNumber = 'SD-'
                . now()->format('Y')
                . '-'
                . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Ticket::query()->where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }
}

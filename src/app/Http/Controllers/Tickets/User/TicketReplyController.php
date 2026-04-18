<?php

namespace App\Http\Controllers\Tickets\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketReply\TicketReplyStoreRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

class TicketReplyController extends Controller
{
    public function store(TicketReplyStoreRequest $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->requester_id === auth()->id(), 403);

        $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'message' => $request->validated('message'),
            'is_internal' => false,
        ]);

        $updateData = [
            'last_reply_at' => now(),
        ];

        if ($ticket->status === Ticket::STATUS_WAITING_FOR_CUSTOMER) {
            $updateData['status'] = Ticket::STATUS_OPEN;
        }

        $ticket->update($updateData);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Your reply has been added.');
    }
}

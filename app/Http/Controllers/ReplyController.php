<?php

namespace App\Http\Controllers;

use App\Models\Reply;
use App\Models\Ticket;
use Illuminate\Http\Request;

class ReplyController extends Controller
{
    public function store(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        if ($ticket->status->isClosed()) {
            return back()->with('error', __('lang.reply_closed_ticket_error'));
        }

        $request->validate(['body' => 'required|string']);

        $ticket->replies()->create([
            'body' => $request->body,
            'author_id' => auth()->id(),
        ]);

        return back()->with('success', __('lang.reply_added_success'));
    }

    public function update(Request $request, Reply $reply)
    {
        $this->authorize('update', $reply);

        $request->validate(['body' => 'required|string']);

        $reply->update(['body' => $request->body]);

        return back()->with('success', __('lang.reply_updated_success'));
    }

    public function destroy(Reply $reply)
    {
        $this->authorize('delete', $reply);

        $reply->delete();

        return back()->with('success', __('lang.reply_deleted_success'));
    }
}

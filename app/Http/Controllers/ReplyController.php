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

        if ($ticket->status->name === 'Закрыта') {
            return back()->with('error', 'Нельзя ответить на закрытую заявку.');
        }

        $request->validate(['body' => 'required|string']);

        $ticket->replies()->create([
            'body' => $request->body,
            'author_id' => auth()->id(),
        ]);

        return back()->with('success', 'Ваш ответ был добавлен.');
    }

    public function update(Request $request, Reply $reply)
    {
        $this->authorize('update', $reply);

        $request->validate(['body' => 'required|string']);

        $reply->update(['body' => $request->body]);

        return back()->with('success', 'Ответ был успешно обновлен.');
    }

    public function destroy(Reply $reply)
    {
        $this->authorize('delete', $reply);

        $reply->delete();

        return back()->with('success', 'Ответ был удален.');
    }
}

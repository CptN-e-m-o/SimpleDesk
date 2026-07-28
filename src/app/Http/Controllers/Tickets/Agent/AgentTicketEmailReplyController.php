<?php

namespace App\Http\Controllers\Tickets\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\Agent\AgentTicketEmailReplyStoreRequest;
use App\Models\Ticket;
use App\Models\User\User;
use App\Services\Admin\Mail\UploadedMailAttachmentFactory;
use App\Services\Tickets\Agent\AgentTicketEmailReplyService;
use Illuminate\Http\RedirectResponse;

class AgentTicketEmailReplyController extends Controller
{
    public function __construct(
        private readonly AgentTicketEmailReplyService $replies,
        private readonly UploadedMailAttachmentFactory $attachments,
    ) {
    }

    public function store(
        AgentTicketEmailReplyStoreRequest $request,
        Ticket $ticket,
    ): RedirectResponse {
        $files = $request->file(
            'attachments',
            []
        );

        $agent = $request->user();

        abort_unless($agent instanceof User, 401);

        $this->replies->create(
            ticket: $ticket,
            agent: $agent,
            message: $request->validated('message'),
            attachments: $this->attachments->makeMany(
                is_array($files) ? $files : []
            ),
        );

        return back()->with(
            'success',
            'Reply was queued for email delivery.'
        );
    }
}

<?php

namespace App\Services\Tickets\Agent;

use App\Data\Admin\Mail\MailAttachmentData;
use App\Exceptions\Admin\Mail\TicketReplyEmailException;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User\User;
use App\Services\Admin\Mail\Ticketing\TicketReplyEmailService;
use App\Services\Tickets\TicketAccessService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class AgentTicketEmailReplyService
{
    public function __construct(
        private readonly TicketAccessService $access,
        private readonly TicketReplyEmailService $mail,
    ) {
    }

    /**
     * @param array<int, MailAttachmentData> $attachments
     */
    public function create(
        Ticket $ticket,
        User $agent,
        string $message,
        array $attachments = [],
    ): TicketReply {
        if (!$agent->hasPermission('agent.tickets.reply')) {
            throw new AuthorizationException(
                'You are not allowed to reply to tickets.'
            );
        }

        if (!$this->access->canView($agent, $ticket)) {
            throw new AuthorizationException(
                'You do not have access to this ticket.'
            );
        }

        if (
            !(bool) config(
                'simpledesk-mail-ticketing.outgoing_replies.enabled',
                true
            )
        ) {
            throw new TicketReplyEmailException(
                message: 'Outgoing ticket email replies are disabled.',
                errorCode: 'outgoing_ticket_replies_disabled',
                retryable: false,
            );
        }

        return DB::transaction(
            function () use (
                $ticket,
                $agent,
                $message,
                $attachments,
            ): TicketReply {
                $lockedTicket = Ticket::query()
                    ->lockForUpdate()
                    ->findOrFail($ticket->id);

                if (!$this->access->canView($agent, $lockedTicket)) {
                    throw new AuthorizationException(
                        'You do not have access to this ticket.'
                    );
                }

                $reply = $lockedTicket
                    ->replies()
                    ->create([
                        'user_id' => $agent->id,
                        'message' => trim($message),
                        'is_internal' => false,
                    ]);

                $emailMessage = $this->mail->queue(
                    ticketReplyId: $reply->id,
                    dispatch: true,
                    attachments: $attachments,
                );

                $lockedTicket->forceFill([
                    'last_reply_at' => now(),
                ])->save();

                $reply->setRelation(
                    'outgoingEmailMessage',
                    $emailMessage
                );

                return $reply;
            },
            3,
        );
    }
}

<?php

namespace App\Services\Admin\Mail\Ticketing;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\MailAttachmentData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Exceptions\Admin\Mail\TicketReplyEmailException;
use App\Jobs\Admin\Mail\SendOutgoingEmailJob;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\TicketReply;
use App\Services\Admin\Mail\OutgoingEmailQueueService;
use Illuminate\Support\Facades\DB;

class TicketReplyEmailService
{
    public function __construct(
        private readonly TicketEmailThreadResolver $threads,
        private readonly TicketReplyEmailRenderer $renderer,
        private readonly OutgoingEmailQueueService $outgoingQueue,
    ) {}

    /**
     * @param  array<int, MailAttachmentData>  $attachments
     */
    public function queue(
        int $ticketReplyId,
        bool $dispatch = true,
        array $attachments = [],
    ): EmailMessage {
        return DB::transaction(
            function () use (
                $ticketReplyId,
                $dispatch,
                $attachments,
            ): EmailMessage {
                $reply = TicketReply::query()
                    ->lockForUpdate()
                    ->find($ticketReplyId);

                if ($reply === null) {
                    throw new TicketReplyEmailException(
                        message: "Ticket reply [{$ticketReplyId}] "
                        .'was not found.',
                        errorCode: 'ticket_reply_not_found',
                        retryable: false,
                    );
                }

                $reply->loadMissing([
                    'ticket.requester',
                    'ticket.mailbox',
                    'ticket.department',
                    'user',
                    'incomingEmailMessage',
                ]);

                $this->assertReplyCanBeSent(
                    $reply
                );

                $ticket = $reply->ticket;

                if ($ticket === null) {
                    throw new TicketReplyEmailException(
                        message: "Ticket reply [{$reply->id}] "
                        .'has no ticket.',
                        errorCode: 'ticket_reply_has_no_ticket',
                        retryable: false,
                    );
                }

                $mailbox = $ticket->mailbox;

                if ($mailbox === null) {
                    throw new TicketReplyEmailException(
                        message: "Ticket [{$ticket->id}] "
                        .'has no mailbox.',
                        errorCode: 'ticket_has_no_mailbox',
                        retryable: false,
                    );
                }

                if (! $mailbox->is_active) {
                    throw new TicketReplyEmailException(
                        message: "Mailbox [{$mailbox->id}] "
                        .'is disabled.',
                        errorCode: 'ticket_mailbox_disabled',
                        retryable: false,
                    );
                }

                $existingMessage =
                    EmailMessage::query()
                        ->where(
                            'ticket_reply_id',
                            $reply->id
                        )
                        ->where(
                            'direction',
                            EmailMessageDirection::Outgoing
                                ->value
                        )
                        ->first();

                if ($existingMessage !== null) {
                    if ($attachments !== []) {
                        if (in_array(
                            $existingMessage->status,
                            [
                                EmailMessageStatus::Sending,
                                EmailMessageStatus::Sent,
                                EmailMessageStatus::Delivered,
                                EmailMessageStatus::Rejected,
                                EmailMessageStatus::Bounced,
                                EmailMessageStatus::Complained,
                            ],
                            true,
                        )) {
                            throw new TicketReplyEmailException(
                                message: "Ticket reply [{$reply->id}] "
                                .'email can no longer be changed.',
                                errorCode: 'ticket_reply_email_immutable',
                                retryable: false,
                            );
                        }

                        return $this->outgoingQueue->queue(
                            mailbox: $mailbox,
                            message: OutgoingEmailMessageData::fromEmailMessage(
                                message: $existingMessage,
                                attachments: $attachments,
                            ),
                            ticketId: $ticket->id,
                            ticketReplyId: $reply->id,
                            dispatch: $dispatch,
                        );
                    }

                    if ($dispatch) {
                        $this->dispatchExistingMessage(
                            $existingMessage
                        );
                    }

                    return $existingMessage->loadMissing(
                        'attachments'
                    );
                }

                $requester = $ticket->requester;

                if ($requester === null) {
                    throw new TicketReplyEmailException(
                        message: "Ticket [{$ticket->id}] "
                        .'has no requester.',
                        errorCode: 'ticket_requester_missing',
                        retryable: false,
                    );
                }

                $recipientEmail = strtolower(
                    trim(
                        (string) $requester->email
                    )
                );

                if (
                    filter_var(
                        $recipientEmail,
                        FILTER_VALIDATE_EMAIL
                    ) === false
                ) {
                    throw new TicketReplyEmailException(
                        message: "Ticket requester [{$requester->id}] "
                        .'has an invalid email address.',
                        errorCode: 'invalid_ticket_requester_email',
                        retryable: false,
                    );
                }

                $mailboxAddress = strtolower(
                    trim(
                        (string) $mailbox
                            ->email_address
                    )
                );

                if (
                    $recipientEmail
                    === $mailboxAddress
                ) {
                    throw new TicketReplyEmailException(
                        message: 'Ticket requester address matches '
                        .'the mailbox address. Sending was '
                        .'blocked to prevent an email loop.',
                        errorCode: 'ticket_reply_email_loop',
                        retryable: false,
                    );
                }

                $thread = $this
                    ->threads
                    ->resolve($ticket);

                $rendered = $this
                    ->renderer
                    ->render($reply);

                $payload =
                    new OutgoingEmailMessageData(
                        idempotencyKey: 'ticket-reply:'
                        .$reply->id
                        .':outgoing:v1',

                        from: null,

                        to: [
                            new MailAddressData(
                                address: $recipientEmail,

                                name: $requester->name,
                            ),
                        ],

                        cc: [],
                        bcc: [],
                        replyTo: [],

                        subject: $rendered->subject,

                        textBody: $rendered->textBody,

                        htmlBody: $rendered->htmlBody,

                        headers: [
                            'X-SimpleDesk-Ticket-ID' => (string) $ticket->id,

                            'X-SimpleDesk-Ticket-Number' => $ticket->ticket_number,

                            'X-SimpleDesk-Ticket-Reply-ID' => (string) $reply->id,
                        ],

                        attachments: $attachments,

                        internetMessageId: null,

                        inReplyToMessageId: $thread
                            ->inReplyToMessageId,

                        references: $thread->references,

                        metadata: [
                            'source' => 'ticket_reply',

                            'ticket_id' => $ticket->id,

                            'ticket_number' => $ticket
                                ->ticket_number,

                            'ticket_reply_id' => $reply->id,

                            'agent_user_id' => $reply->user_id,

                            'requester_user_id' => $requester->id,

                            'parent_email_message_id' => $thread
                                ->parentEmailMessageId,
                        ],
                    );

                return $this
                    ->outgoingQueue
                    ->queue(
                        mailbox: $mailbox,
                        message: $payload,
                        ticketId: $ticket->id,
                        ticketReplyId: $reply->id,
                        dispatch: $dispatch,
                    );
            },
            3,
        );
    }

    private function assertReplyCanBeSent(
        TicketReply $reply
    ): void {
        if ($reply->is_internal) {
            throw new TicketReplyEmailException(
                message: "Ticket reply [{$reply->id}] "
                .'is an internal note and cannot be sent.',
                errorCode: 'internal_ticket_reply',
                retryable: false,
            );
        }

        if ($reply->cameFromIncomingEmail()) {
            throw new TicketReplyEmailException(
                message: "Ticket reply [{$reply->id}] "
                .'originated from an incoming email '
                .'and must not be sent back.',
                errorCode: 'incoming_ticket_reply',
                retryable: false,
            );
        }

        $ticket = $reply->ticket;
        $author = $reply->user;

        if ($author === null) {
            throw new TicketReplyEmailException(
                message: "Ticket reply [{$reply->id}] "
                .'has no author.',
                errorCode: 'ticket_reply_author_missing',
                retryable: false,
            );
        }

        if (
            $ticket !== null
            && $ticket->requester_id === $author->id
        ) {
            throw new TicketReplyEmailException(
                message: "Ticket reply [{$reply->id}] "
                .'was created by the requester and must not '
                .'be sent back to the requester.',
                errorCode: 'requester_ticket_reply',
                retryable: false,
            );
        }

        if (! $author->hasPermission('agent.tickets.reply')) {
            throw new TicketReplyEmailException(
                message: "Ticket reply author [{$author->id}] "
                .'is not allowed to send ticket email replies.',
                errorCode: 'ticket_reply_author_not_agent',
                retryable: false,
            );
        }
    }

    private function dispatchExistingMessage(
        EmailMessage $emailMessage
    ): void {
        if (! in_array(
            $emailMessage->status,
            [
                EmailMessageStatus::Preparing,
                EmailMessageStatus::Queued,
                EmailMessageStatus::Failed,
            ],
            true,
        )) {
            return;
        }

        $pendingDispatch =
            SendOutgoingEmailJob::dispatch(
                $emailMessage->id
            );

        $connection = config(
            'simpledesk-mail-ticketing.outgoing_replies.queue_connection'
        );

        if (
            is_string($connection)
            && $connection !== ''
        ) {
            $pendingDispatch->onConnection(
                $connection
            );
        }

        $pendingDispatch
            ->onQueue(
                (string) config(
                    'simpledesk-mail-ticketing.outgoing_replies.queue',
                    'mail-outgoing'
                )
            )
            ->afterCommit();
    }
}

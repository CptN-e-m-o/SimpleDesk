<?php

namespace App\Services\Admin\Mail\Ticketing;

use App\Models\Admin\Mail\EmailMessage;
use App\Models\Ticket;

class InboundEmailThreadMatcher
{
    public function match(
        EmailMessage $incomingMessage
    ): ?Ticket {
        if ($incomingMessage->mailbox_id === null) {
            return null;
        }

        foreach (
            $this->candidateMessageIds(
                $incomingMessage
            ) as $candidateMessageId
        ) {
            $matchedEmailMessage =
                EmailMessage::query()
                    ->with('ticket')
                    ->where(
                        'id',
                        '!=',
                        $incomingMessage->id
                    )
                    ->where(
                        'mailbox_id',
                        $incomingMessage->mailbox_id
                    )
                    ->whereNotNull(
                        'ticket_id'
                    )
                    ->whereNotNull(
                        'internet_message_id'
                    )
                    ->where(
                        function (
                            $query
                        ) use (
                            $candidateMessageId
                        ): void {
                            $query
                                ->whereRaw(
                                    'LOWER(internet_message_id) = ?',
                                    [
                                        $candidateMessageId,
                                    ]
                                )
                                ->orWhereRaw(
                                    'LOWER(internet_message_id) = ?',
                                    [
                                        '<'
                                        .$candidateMessageId
                                        .'>',
                                    ]
                                );
                        }
                    )
                    ->latest('id')
                    ->first();

            $ticket =
                $matchedEmailMessage?->ticket;

            if ($ticket === null) {
                continue;
            }

            if (
                (int) $ticket->mailbox_id
                !== (int) $incomingMessage->mailbox_id
            ) {
                continue;
            }

            if (
                $ticket->status
                === Ticket::STATUS_CLOSED
                && config(
                    'simpledesk-mail-ticketing.closed_ticket_action',
                    'new_ticket'
                ) === 'new_ticket'
            ) {
                return null;
            }

            return $ticket;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function candidateMessageIds(
        EmailMessage $message
    ): array {
        $candidates = [];

        $inReplyTo = $this->normalizeMessageId(
            $message->in_reply_to_message_id
        );

        if ($inReplyTo !== null) {
            $candidates[] = $inReplyTo;
        }

        $references = array_reverse(
            $message->reference_message_ids
            ?? []
        );

        foreach ($references as $reference) {
            $reference = $this->normalizeMessageId(
                is_scalar($reference)
                    ? (string) $reference
                    : null
            );

            if ($reference !== null) {
                $candidates[] = $reference;
            }
        }

        return array_values(
            array_unique($candidates)
        );
    }

    private function normalizeMessageId(
        ?string $messageId
    ): ?string {
        if ($messageId === null) {
            return null;
        }

        $messageId = strtolower(
            trim(
                $messageId,
                " \t\n\r\0\x0B<>"
            )
        );

        return $messageId !== ''
            ? $messageId
            : null;
    }
}

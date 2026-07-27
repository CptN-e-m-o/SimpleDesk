<?php

namespace App\Services\Admin\Mail\Ticketing;

use App\Data\Admin\Mail\TicketEmailThreadData;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Ticket;

class TicketEmailThreadResolver
{
    public function resolve(
        Ticket $ticket
    ): TicketEmailThreadData {
        $parentMessage = EmailMessage::query()
            ->where(
                'ticket_id',
                $ticket->id
            )
            ->whereNotNull(
                'internet_message_id'
            )
            ->where(
                function ($query): void {
                    $query
                        ->where(
                            function ($query): void {
                                $query
                                    ->where(
                                        'direction',
                                        EmailMessageDirection::Incoming
                                            ->value
                                    )
                                    ->whereIn(
                                        'status',
                                        [
                                            EmailMessageStatus::Received
                                                ->value,
                                            EmailMessageStatus::Processed
                                                ->value,
                                        ]
                                    );
                            }
                        )
                        ->orWhere(
                            function ($query): void {
                                $query
                                    ->where(
                                        'direction',
                                        EmailMessageDirection::Outgoing
                                            ->value
                                    )
                                    ->whereIn(
                                        'status',
                                        [
                                            EmailMessageStatus::Sent
                                                ->value,
                                            EmailMessageStatus::Delivered
                                                ->value,
                                        ]
                                    );
                            }
                        );
                }
            )
            ->latest('id')
            ->first();

        if ($parentMessage === null) {
            return new TicketEmailThreadData(
                parentEmailMessageId: null,
                inReplyToMessageId: null,
                references: [],
            );
        }

        $references = [];

        foreach (
            $parentMessage->reference_message_ids
            ?? []
            as $reference
        ) {
            if (!is_scalar($reference)) {
                continue;
            }

            $reference = $this->normalizeMessageId(
                (string) $reference
            );

            if ($reference !== null) {
                $references[] = $reference;
            }
        }

        $parentInReplyTo =
            $this->normalizeMessageId(
                $parentMessage
                    ->in_reply_to_message_id
            );

        if ($parentInReplyTo !== null) {
            $references[] = $parentInReplyTo;
        }

        $parentMessageId =
            $this->normalizeMessageId(
                $parentMessage
                    ->internet_message_id
            );

        if ($parentMessageId !== null) {
            $references[] = $parentMessageId;
        }

        $references = $this->uniqueMessageIds(
            $references
        );

        $maxReferences = max(
            1,
            (int) config(
                'simpledesk-mail-ticketing.outgoing_replies.max_references',
                50
            )
        );

        if (count($references) > $maxReferences) {
            $references = array_slice(
                $references,
                -$maxReferences
            );
        }

        return new TicketEmailThreadData(
            parentEmailMessageId:
            $parentMessage->id,

            inReplyToMessageId:
            $parentMessageId,

            references:
            $references,
        );
    }

    /**
     * @param array<int, string> $messageIds
     * @return array<int, string>
     */
    private function uniqueMessageIds(
        array $messageIds
    ): array {
        $result = [];
        $known = [];

        foreach ($messageIds as $messageId) {
            $normalizedKey = strtolower(
                $messageId
            );

            if (isset($known[$normalizedKey])) {
                continue;
            }

            $known[$normalizedKey] = true;
            $result[] = $messageId;
        }

        return $result;
    }

    private function normalizeMessageId(
        ?string $messageId
    ): ?string {
        if ($messageId === null) {
            return null;
        }

        $messageId = trim(
            $messageId,
            " \t\n\r\0\x0B<>"
        );

        return $messageId !== ''
            ? $messageId
            : null;
    }
}

<?php

namespace App\Services\Admin\Mail\Ticketing;

use App\Data\Admin\Mail\InboundEmailDecisionData;
use App\Data\Admin\Mail\ParsedInboundEmailContentData;
use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Exceptions\Admin\Mail\InboundEmailAlreadyProcessingException;
use App\Exceptions\Admin\Mail\InboundEmailTicketingException;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User\User;
use App\Services\Admin\Mail\ReplyParsing\InboundEmailClassifier;
use App\Services\Admin\Mail\ReplyParsing\InboundEmailReplyParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class InboundEmailTicketProcessor
{
    public function __construct(
        private readonly InboundEmailRequesterResolver $requesters,
        private readonly InboundEmailThreadMatcher $threads,
        private readonly InboundEmailClassifier $classifier,
        private readonly InboundEmailReplyParser $replyParser,
    ) {}

    public function process(
        int $emailMessageId
    ): EmailMessage {
        try {
            return DB::transaction(
                fn (): EmailMessage => $this->processInTransaction(
                    $emailMessageId
                ),
                3,
            );
        } catch (
            InboundEmailAlreadyProcessingException $exception
        ) {
            throw $exception;
        } catch (
            InboundEmailTicketingException $exception
        ) {
            if (
                $exception->errorCode()
                === 'inbound_attachments_pending'
            ) {
                throw $exception;
            }

            $this->markFailed(
                emailMessageId: $emailMessageId,
                exception: $exception,
            );

            throw $exception;
        } catch (Throwable $exception) {
            $this->markFailed(
                emailMessageId: $emailMessageId,
                exception: $exception,
            );

            throw $exception;
        }
    }

    private function processInTransaction(
        int $emailMessageId
    ): EmailMessage {
        $emailMessage = EmailMessage::query()
            ->with([
                'mailbox',
                'attachments',
                'ticket',
                'ticketReply',
            ])
            ->lockForUpdate()
            ->find($emailMessageId);

        if ($emailMessage === null) {
            throw new InboundEmailTicketingException(
                message: "Email message [{$emailMessageId}] "
                .'was not found.',
                errorCode: 'email_message_not_found',
                retryable: false,
            );
        }

        if (
            $emailMessage->direction
            !== EmailMessageDirection::Incoming
        ) {
            throw new InboundEmailTicketingException(
                message: "Email message [{$emailMessageId}] "
                .'is not incoming.',
                errorCode: 'email_message_not_incoming',
                retryable: false,
            );
        }

        if (
            $emailMessage->status
            === EmailMessageStatus::Processed
        ) {
            return $emailMessage;
        }

        $this->assertMessageCanBeProcessed(
            $emailMessage
        );

        $this->assertAttachmentsCanBeProcessed(
            $emailMessage
        );

        $emailMessage->forceFill([
            'status' => EmailMessageStatus::Processing,

            'processing_started_at' => now(),

            'failed_at' => null,
            'failure_code' => null,
            'failure_message' => null,
        ])->save();

        $decision = $this
            ->classifier
            ->classify($emailMessage);

        if (! $decision->shouldProcess) {
            return $this->markIgnored(
                emailMessage: $emailMessage,
                decision: $decision,
            );
        }

        $parsedContent = $this
            ->replyParser
            ->parse($emailMessage);

        $requester = $this
            ->requesters
            ->resolve($emailMessage);

        $ticket = $this
            ->threads
            ->match($emailMessage);

        if ($ticket === null) {
            $ticket = $this->createTicket(
                emailMessage: $emailMessage,
                requester: $requester,
                body: $parsedContent->body,
            );

            $ticketReply = null;
            $action = 'ticket_created';
        } else {
            $ticketReply = $this->createReply(
                ticket: $ticket,
                requester: $requester,
                emailMessage: $emailMessage,
                body: $parsedContent->body,
            );

            $action = 'reply_created';
        }

        $metadata =
            $emailMessage->metadata
            ?? [];

        $metadata['ticketing'] = [
            'action' => $action,

            'classification' => $decision
                ->classification
                ->value,

            'classification_reason' => $decision->reason,

            'ticket_id' => $ticket->id,

            'ticket_reply_id' => $ticketReply?->id,

            'requester_id' => $requester->id,

            'reply_parsing' => $this->parsingMetadata(
                $parsedContent
            ),

            'processed_at' => now()->toIso8601String(),
        ];

        $emailMessage->forceFill([
            'ticket_id' => $ticket->id,

            'ticket_reply_id' => $ticketReply?->id,

            'status' => EmailMessageStatus::Processed,

            'metadata' => $metadata,

            'processing_started_at' => null,
            'processed_at' => now(),

            'failed_at' => null,
            'failure_code' => null,
            'failure_message' => null,
        ])->save();

        return $emailMessage->fresh([
            'ticket',
            'ticketReply',
            'attachments',
            'mailbox',
        ]);
    }

    private function markIgnored(
        EmailMessage $emailMessage,
        InboundEmailDecisionData $decision,
    ): EmailMessage {
        $metadata =
            $emailMessage->metadata
            ?? [];

        $metadata['ticketing'] = [
            'action' => 'ignored',

            'classification' => $decision
                ->classification
                ->value,

            'classification_reason' => $decision->reason,

            'ticket_id' => null,
            'ticket_reply_id' => null,
            'requester_id' => null,

            'processed_at' => now()->toIso8601String(),
        ];

        $emailMessage->forceFill([
            'ticket_id' => null,
            'ticket_reply_id' => null,

            'status' => EmailMessageStatus::Processed,

            'metadata' => $metadata,

            'processing_started_at' => null,
            'processed_at' => now(),

            'failed_at' => null,
            'failure_code' => null,
            'failure_message' => null,
        ])->save();

        return $emailMessage->fresh([
            'attachments',
            'mailbox',
        ]);
    }

    private function parsingMetadata(
        ParsedInboundEmailContentData $content
    ): array {
        return [
            'source' => $content->source,

            'quoted_text_removed' => $content
                ->quotedTextRemoved,

            'signature_removed' => $content
                ->signatureRemoved,

            'original_length' => $content
                ->originalLength,

            'parsed_length' => $content
                ->parsedLength,
        ];
    }

    private function assertMessageCanBeProcessed(
        EmailMessage $emailMessage
    ): void {
        if (
            $emailMessage->status
            === EmailMessageStatus::Processing
            && $emailMessage
                ->processing_started_at
            !== null
        ) {
            $lockSeconds = (int) config(
                'simpledesk-mail-ticketing.processing_lock_seconds',
                600
            );

            if (
                $emailMessage
                    ->processing_started_at
                    ->greaterThan(
                        now()->subSeconds(
                            $lockSeconds
                        )
                    )
            ) {
                throw new InboundEmailAlreadyProcessingException(
                    $emailMessage->id
                );
            }

            return;
        }

        if (
            $emailMessage->status
            === EmailMessageStatus::Received
        ) {
            return;
        }

        if (
            $emailMessage->status
            === EmailMessageStatus::Failed
            && $emailMessage->failure_code
            === 'inbound_ticket_processing_failed'
        ) {
            return;
        }

        throw new InboundEmailTicketingException(
            message: "Email message [{$emailMessage->id}] "
            .'with status '
            ."[{$emailMessage->status->value}] "
            .'cannot be processed as a ticket.',
            errorCode: 'invalid_email_message_status',
            retryable: false,
        );
    }

    private function assertAttachmentsCanBeProcessed(
        EmailMessage $emailMessage
    ): void {
        if (
            ! (bool) config(
                'simpledesk-mail-antivirus.enabled',
                false
            )
            || $emailMessage->attachments->isEmpty()
        ) {
            return;
        }

        $infectedAttachmentIds = $emailMessage
            ->attachments
            ->filter(
                fn ($attachment): bool => $attachment->scan_status
                    === EmailAttachmentScanStatus::Infected
            )
            ->pluck('id')
            ->values()
            ->all();

        if ($infectedAttachmentIds !== []) {
            throw new InboundEmailTicketingException(
                message: "Email message [{$emailMessage->id}] "
                .'contains infected attachments: '
                .implode(
                    ', ',
                    $infectedAttachmentIds
                )
                .'.',
                errorCode: 'inbound_attachment_infected',
                retryable: false,
            );
        }

        $failedAttachmentIds = $emailMessage
            ->attachments
            ->filter(
                fn ($attachment): bool => $attachment->scan_status
                    === EmailAttachmentScanStatus::Failed
            )
            ->pluck('id')
            ->values()
            ->all();

        if ($failedAttachmentIds !== []) {
            throw new InboundEmailTicketingException(
                message: "Email message [{$emailMessage->id}] "
                .'contains attachments that failed '
                .'antivirus scanning: '
                .implode(
                    ', ',
                    $failedAttachmentIds
                )
                .'.',
                errorCode: 'inbound_attachment_scan_failed',
                retryable: false,
            );
        }

        $pendingAttachmentIds = $emailMessage
            ->attachments
            ->filter(
                fn ($attachment): bool => in_array(
                    $attachment->scan_status,
                    [
                        EmailAttachmentScanStatus::NotScanned,
                        EmailAttachmentScanStatus::Pending,
                    ],
                    true,
                )
            )
            ->pluck('id')
            ->values()
            ->all();

        if ($pendingAttachmentIds !== []) {
            throw new InboundEmailTicketingException(
                message: "Email message [{$emailMessage->id}] "
                .'is waiting for antivirus scanning '
                .'of attachments: '
                .implode(
                    ', ',
                    $pendingAttachmentIds
                )
                .'.',
                errorCode: 'inbound_attachments_pending',
                retryable: true,
            );
        }

        $unexpectedAttachmentIds = $emailMessage
            ->attachments
            ->filter(
                fn ($attachment): bool => $attachment->scan_status
                    !== EmailAttachmentScanStatus::Clean
            )
            ->pluck('id')
            ->values()
            ->all();

        if ($unexpectedAttachmentIds !== []) {
            throw new InboundEmailTicketingException(
                message: "Email message [{$emailMessage->id}] "
                .'contains attachments with unsupported '
                .'antivirus statuses: '
                .implode(
                    ', ',
                    $unexpectedAttachmentIds
                )
                .'.',
                errorCode: 'inbound_attachment_scan_status_invalid',
                retryable: false,
            );
        }
    }

    private function createTicket(
        EmailMessage $emailMessage,
        User $requester,
        string $body,
    ): Ticket {
        $status = (string) config(
            'simpledesk-mail-ticketing.default_status',
            Ticket::STATUS_OPEN
        );

        if (
            ! in_array(
                $status,
                Ticket::statuses(),
                true
            )
        ) {
            $status = Ticket::STATUS_OPEN;
        }

        $priority = (string) config(
            'simpledesk-mail-ticketing.default_priority',
            Ticket::PRIORITY_MEDIUM
        );

        if (
            ! in_array(
                $priority,
                Ticket::priorities(),
                true
            )
        ) {
            $priority =
                Ticket::PRIORITY_MEDIUM;
        }

        return Ticket::query()->create([
            'ticket_number' => $this->ticketNumber(),

            'requester_id' => $requester->id,

            'category_id' => null,
            'assignee_id' => null,

            'mailbox_id' => $emailMessage->mailbox_id,

            'department_id' => $emailMessage
                ->mailbox
                ?->department_id,

            'subject' => $this->ticketSubject(
                $emailMessage
            ),

            'priority' => $priority,
            'status' => $status,
            'source' => Ticket::SOURCE_EMAIL,

            'service' => null,
            'description' => $body,

            'last_reply_at' => $emailMessage->received_at
                ?? now(),

            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    private function createReply(
        Ticket $ticket,
        User $requester,
        EmailMessage $emailMessage,
        string $body,
    ): TicketReply {
        $ticket = Ticket::query()
            ->lockForUpdate()
            ->findOrFail($ticket->id);

        $reply = $ticket
            ->replies()
            ->create([
                'user_id' => $requester->id,

                'message' => $body,

                'is_internal' => false,
            ]);

        $updates = [
            'last_reply_at' => $emailMessage->received_at
                ?? now(),
        ];

        $replyStatus = (string) config(
            'simpledesk-mail-ticketing.customer_reply_status',
            Ticket::STATUS_OPEN
        );

        if (
            ! in_array(
                $replyStatus,
                Ticket::statuses(),
                true
            )
        ) {
            $replyStatus =
                Ticket::STATUS_OPEN;
        }

        if (
            $ticket->status
            === Ticket::STATUS_WAITING_FOR_CUSTOMER
        ) {
            $updates['status'] =
                $replyStatus;
        }

        if (
            $ticket->status
            === Ticket::STATUS_RESOLVED
            && (bool) config(
                'simpledesk-mail-ticketing.reopen_resolved_tickets',
                true
            )
        ) {
            $updates['status'] =
                $replyStatus;

            $updates['resolved_at'] =
                null;
        }

        if (
            $ticket->status
            === Ticket::STATUS_CLOSED
            && config(
                'simpledesk-mail-ticketing.closed_ticket_action',
                'new_ticket'
            ) === 'reopen'
        ) {
            $updates['status'] =
                $replyStatus;

            $updates['resolved_at'] =
                null;

            $updates['closed_at'] =
                null;
        }

        $ticket->forceFill(
            $updates
        )->save();

        return $reply;
    }

    private function ticketSubject(
        EmailMessage $emailMessage
    ): string {
        $subject = trim(
            (string) $emailMessage->subject
        );

        $subject = preg_replace(
            '/^(?:(?:re|fw|fwd|aw|sv)\s*:\s*)+/iu',
            '',
            $subject
        );

        $subject = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $subject)
        );

        if ($subject === '') {
            $subject = (string) config(
                'simpledesk-mail-ticketing.subject_fallback',
                'Обращение по электронной почте'
            );
        }

        return mb_substr(
            $subject,
            0,
            255
        );
    }

    private function ticketNumber(): string
    {
        $prefix = strtoupper(
            preg_replace(
                '/[^A-Z0-9]+/i',
                '',
                (string) config(
                    'simpledesk-mail-ticketing.ticket_number_prefix',
                    'SD'
                )
            )
        );

        if ($prefix === '') {
            $prefix = 'SD';
        }

        do {
            $number = sprintf(
                '%s-%s-%s',
                $prefix,
                now()->format('Ymd'),
                strtoupper(
                    substr(
                        (string) Str::ulid(),
                        -10
                    )
                ),
            );
        } while (
            Ticket::query()
                ->where(
                    'ticket_number',
                    $number
                )
                ->exists()
        );

        return $number;
    }

    private function markFailed(
        int $emailMessageId,
        Throwable $exception,
    ): void {
        $errorCode =
            $exception
            instanceof InboundEmailTicketingException
                ? $exception->errorCode()
                : 'inbound_ticket_processing_failed';

        $emailMessage = EmailMessage::query()
            ->find($emailMessageId);

        if (
            $emailMessage === null
            || $emailMessage->status
            === EmailMessageStatus::Processed
        ) {
            return;
        }

        $metadata =
            $emailMessage->metadata
            ?? [];

        $metadata['ticketing_failure'] = [
            'error_code' => $errorCode,

            'exception' => $exception::class,

            'failed_at' => now()->toIso8601String(),
        ];

        $emailMessage->forceFill([
            'status' => EmailMessageStatus::Failed,

            'metadata' => $metadata,

            'processing_started_at' => null,

            'failed_at' => now(),

            'failure_code' => 'inbound_ticket_processing_failed',

            'failure_message' => $exception->getMessage(),
        ])->save();
    }
}

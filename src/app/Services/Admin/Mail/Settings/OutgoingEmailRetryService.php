<?php

namespace App\Services\Admin\Mail\Settings;

use App\Data\Admin\Mail\MailAdminActionResultData;
use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Exceptions\Admin\Mail\MailAdminActionException;
use App\Jobs\Admin\Mail\SendOutgoingEmailJob;
use App\Models\Admin\Mail\EmailMessage;
use Illuminate\Support\Facades\DB;
use Throwable;

class OutgoingEmailRetryService
{
    public function __construct(
        private readonly MailAdminActionLock $locks,
    ) {
    }

    public function dispatch(
        EmailMessage $emailMessage,
        ?int $requestedById = null,
    ): MailAdminActionResultData {
        $emailMessage->refresh();

        if (in_array(
            $emailMessage->status,
            [
                EmailMessageStatus::Queued,
                EmailMessageStatus::Sending,
            ],
            true,
        )) {
            return new MailAdminActionResultData(
                action: 'outgoing_message_retry',
                dispatched: false,
                message: 'Outgoing message delivery is already queued or in progress.',
                details: [
                    'email_message_id' => $emailMessage->id,
                ],
            );
        }

        if (!$this->locks->acquire(
            'retry-message',
            $emailMessage->id
        )) {
            return new MailAdminActionResultData(
                action: 'outgoing_message_retry',
                dispatched: false,
                message: 'Outgoing message retry is already queued.',
                details: [
                    'email_message_id' => $emailMessage->id,
                ],
            );
        }

        try {
            $emailMessage = DB::transaction(
                function () use (
                    $emailMessage,
                    $requestedById,
                ): EmailMessage {
                    $lockedMessage = EmailMessage::query()
                        ->with([
                            'mailbox',
                            'attachments',
                        ])
                        ->lockForUpdate()
                        ->findOrFail($emailMessage->id);

                    $this->assertCanRetry(
                        $lockedMessage
                    );

                    $metadata = is_array($lockedMessage->metadata)
                        ? $lockedMessage->metadata
                        : [];

                    $events = is_array(
                        $metadata['admin_actions'] ?? null
                    )
                        ? $metadata['admin_actions']
                        : [];

                    $events[] = [
                        'action' => 'outgoing_retry_requested',
                        'requested_by_id' => $requestedById,
                        'created_at' => now()->toIso8601String(),
                    ];

                    $metadata['admin_actions'] = array_slice(
                        $events,
                        -50
                    );

                    $lockedMessage->forceFill([
                        'status' => EmailMessageStatus::Queued,
                        'mailbox_channel_id' => null,
                        'driver' => null,
                        'queued_at' => now(),
                        'processing_started_at' => null,
                        'processed_at' => null,
                        'sent_at' => null,
                        'delivered_at' => null,
                        'failed_at' => null,
                        'failure_code' => null,
                        'failure_message' => null,
                        'metadata' => $metadata,
                    ])->save();

                    return $lockedMessage;
                },
                3,
            );

            $pendingDispatch = SendOutgoingEmailJob::dispatch(
                $emailMessage->id
            );

            $connection = config(
                'simpledesk-mail-ticketing.outgoing_replies.queue_connection'
            );

            if (
                is_string($connection)
                && trim($connection) !== ''
            ) {
                $pendingDispatch->onConnection(
                    trim($connection)
                );
            }

            $pendingDispatch
                ->onQueue((string) config(
                    'simpledesk-mail.queues.outgoing',
                    'mail-outgoing',
                ))
                ->afterCommit();
        } catch (Throwable $exception) {
            $this->locks->release(
                'retry-message',
                $emailMessage->id
            );

            throw $exception;
        }

        return new MailAdminActionResultData(
            action: 'outgoing_message_retry',
            dispatched: true,
            message: 'Outgoing message retry was queued.',
            details: [
                'email_message_id' => $emailMessage->id,
            ],
        );
    }

    private function assertCanRetry(
        EmailMessage $emailMessage
    ): void {
        if (
            $emailMessage->direction
            !== EmailMessageDirection::Outgoing
        ) {
            throw new MailAdminActionException(
                message: "Email message [{$emailMessage->id}] is not outgoing.",
                errorCode: 'email_message_not_outgoing',
                field: 'message',
            );
        }

        if (
            $emailMessage->mailbox === null
            || !$emailMessage->mailbox->is_active
        ) {
            throw new MailAdminActionException(
                message: "The mailbox for email message [{$emailMessage->id}] is unavailable or disabled.",
                errorCode: 'email_message_mailbox_unavailable',
                field: 'message',
            );
        }

        if (
            $emailMessage->status
            !== EmailMessageStatus::Failed
        ) {
            throw new MailAdminActionException(
                message: "Email message [{$emailMessage->id}] cannot be retried from status [{$emailMessage->status->value}].",
                errorCode: 'email_message_status_not_retryable',
                field: 'message',
            );
        }

        $infectedAttachment = $emailMessage
            ->attachments
            ->first(
                fn ($attachment): bool =>
                    $attachment->scan_status
                    === EmailAttachmentScanStatus::Infected
            );

        if ($infectedAttachment !== null) {
            throw new MailAdminActionException(
                message: "Email message [{$emailMessage->id}] contains an infected attachment.",
                errorCode: 'email_message_has_infected_attachment',
                field: 'message',
            );
        }

        if (!config(
            'simpledesk-mail-antivirus.enabled',
            false
        )) {
            return;
        }

        $unscannedAttachment = $emailMessage
            ->attachments
            ->first(
                fn ($attachment): bool =>
                    $attachment->scan_status
                    !== EmailAttachmentScanStatus::Clean
            );

        if ($unscannedAttachment !== null) {
            throw new MailAdminActionException(
                message: "All attachments for email message [{$emailMessage->id}] must pass antivirus scanning before retry.",
                errorCode: 'email_message_attachments_not_clean',
                field: 'message',
            );
        }
    }
}

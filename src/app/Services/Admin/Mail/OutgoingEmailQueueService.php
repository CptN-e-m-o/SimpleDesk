<?php

namespace App\Services\Admin\Mail;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Jobs\Admin\Mail\SendOutgoingEmailJob;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use InvalidArgumentException;
use Throwable;

class OutgoingEmailQueueService
{
    public function __construct(
        private readonly MailAttachmentStorageService $attachmentStorage,
        private readonly OutgoingMailAttachmentValidator $attachmentValidator,
        private readonly MailInternetMessageIdFactory $messageIds,
    ) {}

    public function queue(
        Mailbox $mailbox,
        OutgoingEmailMessageData $message,
        ?int $ticketId = null,
        ?int $ticketReplyId = null,
        bool $dispatch = true,
    ): EmailMessage {
        if (! $mailbox->is_active) {
            throw new InvalidArgumentException(
                "Mailbox [{$mailbox->id}] is disabled."
            );
        }

        $this->attachmentValidator->validate(
            $message->attachments
        );

        $from = $message->from
            ?? new MailAddressData(
                address: $mailbox->email_address,
                name: $mailbox->display_name
                ?? $mailbox->name,
            );

        $internetMessageId =
            $message->internetMessageId
            ?? $this->messageIds->make(
                mailbox: $mailbox,
                idempotencyKey: $message->idempotencyKey,
            );

        $emailMessage = EmailMessage::query()
            ->firstOrCreate(
                [
                    'idempotency_key' => $message->idempotencyKey,
                ],
                [
                    'mailbox_id' => $mailbox->id,
                    'ticket_id' => $ticketId,
                    'ticket_reply_id' => $ticketReplyId,
                    'direction' => EmailMessageDirection::Outgoing,
                    'driver' => null,
                    'status' => EmailMessageStatus::Preparing,
                    'internet_message_id' => $internetMessageId,
                    'in_reply_to_message_id' => $message->inReplyToMessageId,
                    'reference_message_ids' => $message->references,
                    'sender_address' => $from->address,
                    'sender_name' => $from->name,
                    'to_recipients' => $this->addressesToArray(
                        $message->to
                    ),
                    'cc_recipients' => $this->addressesToArray(
                        $message->cc
                    ),
                    'bcc_recipients' => $this->addressesToArray(
                        $message->bcc
                    ),
                    'reply_to_recipients' => $this->addressesToArray(
                        $message->replyTo
                    ),
                    'subject' => $message->subject,
                    'text_body' => $message->textBody,
                    'html_body' => $message->htmlBody,
                    'headers' => $message->headers,
                    'metadata' => $this->metadataWithDispatchIntent(
                        metadata: $message->metadata,
                        dispatch: $dispatch,
                    ),
                ]
            );

        if ($this->isImmutable($emailMessage)) {
            return $emailMessage->loadMissing(
                'attachments'
            );
        }

        $this->rememberDispatchIntent(
            emailMessage: $emailMessage,
            dispatch: $dispatch,
        );

        $storedInThisCall = [];

        try {
            foreach (
                array_values($message->attachments) as $position => $attachment
            ) {
                $storedAttachment = $this
                    ->attachmentStorage
                    ->store(
                        emailMessage: $emailMessage,
                        attachment: $attachment,
                        position: $position,
                    );

                if ($storedAttachment->wasRecentlyCreated) {
                    $storedInThisCall[] =
                        $storedAttachment;
                }
            }

            $emailMessage->load(
                'attachments'
            );

            $scanState = $this->attachmentScanState(
                $emailMessage
            );

            if ($scanState === 'infected') {
                $emailMessage->forceFill([
                    'status' => EmailMessageStatus::Failed,
                    'queued_at' => null,
                    'processing_started_at' => null,
                    'failed_at' => now(),
                    'failure_code' => 'attachment_infected',
                    'failure_message' => 'Outgoing email contains an infected attachment.',
                ])->save();
            } elseif ($scanState === 'failed') {
                $emailMessage->forceFill([
                    'status' => EmailMessageStatus::Failed,
                    'queued_at' => null,
                    'processing_started_at' => null,
                    'failed_at' => now(),
                    'failure_code' => 'attachment_scan_failed',
                    'failure_message' => 'Antivirus scanning failed for an outgoing attachment.',
                ])->save();
            } elseif ($scanState === 'waiting') {
                $emailMessage->forceFill([
                    'status' => EmailMessageStatus::Preparing,
                    'queued_at' => null,
                    'processing_started_at' => null,
                    'processed_at' => null,
                    'failed_at' => null,
                    'failure_code' => null,
                    'failure_message' => null,
                ])->save();
            } elseif (
                in_array(
                    $emailMessage->status,
                    [
                        EmailMessageStatus::Preparing,
                        EmailMessageStatus::Failed,
                    ],
                    true,
                )
            ) {
                $emailMessage->forceFill([
                    'status' => EmailMessageStatus::Queued,
                    'queued_at' => now(),
                    'processing_started_at' => null,
                    'processed_at' => null,
                    'failed_at' => null,
                    'failure_code' => null,
                    'failure_message' => null,
                ])->save();
            }

            if (
                $dispatch
                && $emailMessage->status
                === EmailMessageStatus::Queued
            ) {
                SendOutgoingEmailJob::dispatch(
                    $emailMessage->id
                )
                    ->onQueue(
                        (string) config(
                            'simpledesk-mail.queues.outgoing',
                            'mail-outgoing'
                        )
                    )
                    ->afterCommit();
            }

            return $emailMessage->fresh(
                'attachments'
            );
        } catch (Throwable $exception) {
            foreach (
                array_reverse($storedInThisCall) as $storedAttachment
            ) {
                try {
                    $this->attachmentStorage->delete(
                        $storedAttachment
                    );
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            if (! $this->isImmutable($emailMessage)) {
                $emailMessage->forceFill([
                    'status' => EmailMessageStatus::Failed,
                    'failed_at' => now(),
                    'failure_code' => 'outgoing_preparation_failed',
                    'failure_message' => $exception->getMessage(),
                ])->save();
            }

            throw $exception;
        }
    }

    private function attachmentScanState(
        EmailMessage $emailMessage
    ): string {
        if ($emailMessage->attachments->isEmpty()) {
            return 'ready';
        }

        if (
            $emailMessage->attachments->contains(
                static fn ($attachment): bool => $attachment->quarantined_at !== null
                    || $attachment->scan_status
                    === EmailAttachmentScanStatus::Infected
            )
        ) {
            return 'infected';
        }

        if (
            $emailMessage->attachments->contains(
                static fn ($attachment): bool => $attachment->scan_status
                    === EmailAttachmentScanStatus::Failed
            )
        ) {
            return 'failed';
        }

        $antivirusEnabled = (bool) config(
            'simpledesk-mail-antivirus.enabled',
            false
        );

        if (
            $emailMessage->attachments->contains(
                static function ($attachment) use (
                    $antivirusEnabled
                ): bool {
                    if (
                        $attachment->scan_status
                        === EmailAttachmentScanStatus::Pending
                    ) {
                        return true;
                    }

                    return $antivirusEnabled
                        && $attachment->scan_status
                        === EmailAttachmentScanStatus::NotScanned;
                }
            )
        ) {
            return 'waiting';
        }

        return 'ready';
    }

    private function rememberDispatchIntent(
        EmailMessage $emailMessage,
        bool $dispatch,
    ): void {
        $metadata = is_array($emailMessage->metadata)
            ? $emailMessage->metadata
            : [];

        $existingIntent = (bool) data_get(
            $metadata,
            'mail_pipeline.dispatch_after_attachment_scan',
            false
        );

        data_set(
            $metadata,
            'mail_pipeline.dispatch_after_attachment_scan',
            $existingIntent || $dispatch
        );

        $emailMessage->forceFill([
            'metadata' => $metadata,
        ])->save();
    }

    private function metadataWithDispatchIntent(
        array $metadata,
        bool $dispatch,
    ): array {
        data_set(
            $metadata,
            'mail_pipeline.dispatch_after_attachment_scan',
            $dispatch
        );

        return $metadata;
    }

    private function isImmutable(
        EmailMessage $emailMessage
    ): bool {
        return in_array(
            $emailMessage->status,
            [
                EmailMessageStatus::Sending,
                EmailMessageStatus::Sent,
                EmailMessageStatus::Delivered,
                EmailMessageStatus::Rejected,
                EmailMessageStatus::Bounced,
                EmailMessageStatus::Complained,
            ],
            true,
        );
    }

    /**
     * @param  array<int, MailAddressData>  $addresses
     */
    private function addressesToArray(
        array $addresses
    ): array {
        return array_map(
            static fn (
                MailAddressData $address
            ): array => $address->toArray(),
            $addresses,
        );
    }
}

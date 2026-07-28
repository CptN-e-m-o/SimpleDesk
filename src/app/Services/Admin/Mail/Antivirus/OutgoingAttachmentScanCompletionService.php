<?php

namespace App\Services\Admin\Mail\Antivirus;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Jobs\Admin\Mail\SendOutgoingEmailJob;
use App\Models\Admin\Mail\EmailMessage;
use Illuminate\Support\Facades\DB;

class OutgoingAttachmentScanCompletionService
{
    public function refresh(
        int $emailMessageId
    ): void {
        $shouldDispatch = DB::transaction(
            function () use (
                $emailMessageId
            ): bool {
                $message = EmailMessage::query()
                    ->lockForUpdate()
                    ->find($emailMessageId);

                if (
                    $message === null
                    || $message->direction
                    !== EmailMessageDirection::Outgoing
                ) {
                    return false;
                }

                if (
                    in_array(
                        $message->status,
                        [
                            EmailMessageStatus::Sending,
                            EmailMessageStatus::Sent,
                            EmailMessageStatus::Delivered,
                            EmailMessageStatus::Rejected,
                            EmailMessageStatus::Bounced,
                            EmailMessageStatus::Complained,
                        ],
                        true,
                    )
                ) {
                    return false;
                }

                $attachments = $message
                    ->attachments()
                    ->get();

                if ($attachments->isEmpty()) {
                    return false;
                }

                if (
                    $attachments->contains(
                        static fn ($attachment): bool =>
                            $attachment->quarantined_at !== null
                            || $attachment->scan_status
                            === EmailAttachmentScanStatus::Infected
                    )
                ) {
                    $message->forceFill([
                        'status' => EmailMessageStatus::Failed,
                        'queued_at' => null,
                        'processing_started_at' => null,
                        'failed_at' => now(),
                        'failure_code' => 'attachment_infected',
                        'failure_message' =>
                            'Outgoing email contains an infected attachment.',
                    ])->save();

                    return false;
                }

                if (
                    $attachments->contains(
                        static fn ($attachment): bool =>
                            $attachment->scan_status
                            === EmailAttachmentScanStatus::Failed
                    )
                ) {
                    $message->forceFill([
                        'status' => EmailMessageStatus::Failed,
                        'queued_at' => null,
                        'processing_started_at' => null,
                        'failed_at' => now(),
                        'failure_code' => 'attachment_scan_failed',
                        'failure_message' =>
                            'Antivirus scanning failed for an outgoing attachment.',
                    ])->save();

                    return false;
                }

                $antivirusEnabled = (bool) config(
                    'simpledesk-mail-antivirus.enabled',
                    false
                );

                $hasUnfinishedScan = $attachments->contains(
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
                );

                if ($hasUnfinishedScan) {
                    if (
                        $message->status
                        !== EmailMessageStatus::Preparing
                    ) {
                        $message->forceFill([
                            'status' => EmailMessageStatus::Preparing,
                            'queued_at' => null,
                            'processing_started_at' => null,
                            'failed_at' => null,
                            'failure_code' => null,
                            'failure_message' => null,
                        ])->save();
                    }

                    return false;
                }

                if (
                    $message->status
                    === EmailMessageStatus::Queued
                ) {
                    return false;
                }

                $message->forceFill([
                    'status' => EmailMessageStatus::Queued,
                    'queued_at' => now(),
                    'processing_started_at' => null,
                    'processed_at' => null,
                    'failed_at' => null,
                    'failure_code' => null,
                    'failure_message' => null,
                ])->save();

                $metadata = is_array($message->metadata)
                    ? $message->metadata
                    : [];

                return (bool) data_get(
                    $metadata,
                    'mail_pipeline.dispatch_after_attachment_scan',
                    true
                );
            },
            3,
        );

        if (!$shouldDispatch) {
            return;
        }

        SendOutgoingEmailJob::dispatch(
            $emailMessageId
        )
            ->onQueue(
                (string) config(
                    'simpledesk-mail.queues.outgoing',
                    'mail-outgoing'
                )
            )
            ->afterCommit();
    }
}

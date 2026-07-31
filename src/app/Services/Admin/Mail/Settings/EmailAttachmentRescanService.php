<?php

namespace App\Services\Admin\Mail\Settings;

use App\Data\Admin\Mail\MailAdminActionResultData;
use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Exceptions\Admin\Mail\MailAdminActionException;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EmailAttachmentRescanService
{
    public function __construct(
        private readonly MailAdminActionLock $locks,
        private readonly ConfiguredAttachmentScanJobDispatcher $dispatcher,
    ) {}

    public function dispatch(
        EmailAttachment $attachment,
        ?int $requestedById = null,
    ): MailAdminActionResultData {
        if (! config(
            'simpledesk-mail-antivirus.enabled',
            false
        )) {
            throw new MailAdminActionException(
                message: 'Attachment antivirus scanning is disabled.',
                errorCode: 'attachment_antivirus_disabled',
                field: 'attachment',
            );
        }

        if (
            trim((string) $attachment->disk) === ''
            || trim((string) $attachment->path) === ''
            || ! Storage::disk($attachment->disk)
                ->exists($attachment->path)
        ) {
            throw new MailAdminActionException(
                message: "The stored file for attachment [{$attachment->id}] does not exist.",
                errorCode: 'attachment_file_missing',
                field: 'attachment',
            );
        }

        if (! $this->locks->acquire(
            'rescan-attachment',
            $attachment->id
        )) {
            return new MailAdminActionResultData(
                action: 'attachment_rescan',
                dispatched: false,
                message: 'Attachment rescan is already queued.',
                details: [
                    'attachment_id' => $attachment->id,
                    'email_message_id' => $attachment->email_message_id,
                ],
            );
        }

        try {
            $attachment = DB::transaction(
                function () use (
                    $attachment,
                    $requestedById,
                ): EmailAttachment {
                    $lockedAttachment = EmailAttachment::query()
                        ->with('emailMessage')
                        ->lockForUpdate()
                        ->findOrFail($attachment->id);

                    $metadata = is_array($lockedAttachment->metadata)
                        ? $lockedAttachment->metadata
                        : [];

                    $events = is_array(
                        $metadata['admin_actions'] ?? null
                    )
                        ? $metadata['admin_actions']
                        : [];

                    $events[] = [
                        'action' => 'attachment_rescan_requested',
                        'previous_scan_status' => $lockedAttachment->scan_status->value,
                        'requested_by_id' => $requestedById,
                        'created_at' => now()->toIso8601String(),
                    ];

                    $metadata['admin_actions'] = array_slice(
                        $events,
                        -50
                    );

                    $lockedAttachment->forceFill([
                        'scan_status' => EmailAttachmentScanStatus::Pending,
                        'scanned_at' => null,
                        'scan_result' => null,
                        'metadata' => $metadata,
                    ])->save();

                    $this->prepareOutgoingMessageForRescan(
                        $lockedAttachment->emailMessage
                    );

                    return $lockedAttachment;
                },
                3,
            );

            $this->dispatcher->dispatch(
                $attachment->id
            );
        } catch (Throwable $exception) {
            $this->locks->release(
                'rescan-attachment',
                $attachment->id
            );

            throw $exception;
        }

        return new MailAdminActionResultData(
            action: 'attachment_rescan',
            dispatched: true,
            message: 'Attachment rescan was queued.',
            details: [
                'attachment_id' => $attachment->id,
                'email_message_id' => $attachment->email_message_id,
                'scan_status' => EmailAttachmentScanStatus::Pending->value,
            ],
        );
    }

    private function prepareOutgoingMessageForRescan(
        ?EmailMessage $emailMessage
    ): void {
        if (
            $emailMessage === null
            || $emailMessage->direction
            !== EmailMessageDirection::Outgoing
            || ! in_array(
                $emailMessage->status,
                [
                    EmailMessageStatus::Failed,
                    EmailMessageStatus::Rejected,
                ],
                true,
            )
            || ! $this->isAttachmentSecurityFailure(
                $emailMessage->failure_code
            )
        ) {
            return;
        }

        $emailMessage->forceFill([
            'status' => EmailMessageStatus::Preparing,
            'queued_at' => null,
            'processing_started_at' => null,
            'processed_at' => null,
            'failed_at' => null,
            'failure_code' => null,
            'failure_message' => null,
        ])->save();
    }

    private function isAttachmentSecurityFailure(
        ?string $failureCode
    ): bool {
        $failureCode = strtolower(
            trim((string) $failureCode)
        );

        if ($failureCode === '') {
            return false;
        }

        foreach (
            [
                'attachment',
                'antivirus',
                'virus',
                'infected',
                'scan',
            ] as $part
        ) {
            if (str_contains($failureCode, $part)) {
                return true;
            }
        }

        return false;
    }
}

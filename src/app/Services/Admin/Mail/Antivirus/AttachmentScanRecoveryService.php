<?php

namespace App\Services\Admin\Mail\Antivirus;

use App\Data\Admin\Mail\AttachmentScanRecoveryResultData;
use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Models\Admin\Mail\EmailAttachment;
use Illuminate\Support\Facades\DB;

class AttachmentScanRecoveryService
{
    public function __construct(
        private readonly AttachmentScanDispatcher $dispatcher,
    ) {
    }

    public function recover(
        ?int $limit = null
    ): AttachmentScanRecoveryResultData {
        if (
            !(bool) config(
                'simpledesk-mail-antivirus.enabled',
                false
            )
        ) {
            return new AttachmentScanRecoveryResultData(
                stuckScansReset: 0,
                pendingScansDispatched: 0,
            );
        }

        $limit ??= (int) config(
            'simpledesk-mail-antivirus.recovery.batch_size',
            100
        );

        $limit = max(
            1,
            min(1000, $limit)
        );

        $stuckScansReset = $this->resetStuckScans(
            $limit
        );

        $pendingScansDispatched = $this->dispatchPendingScans(
            $limit
        );

        return new AttachmentScanRecoveryResultData(
            stuckScansReset: $stuckScansReset,
            pendingScansDispatched: $pendingScansDispatched,
        );
    }

    private function resetStuckScans(
        int $limit
    ): int {
        $timeoutSeconds = max(
            60,
            (int) config(
                'simpledesk-mail-antivirus.recovery.stuck_timeout_seconds',
                600
            )
        );

        $cutoff = now()->subSeconds(
            $timeoutSeconds
        );

        $ids = EmailAttachment::query()
            ->where(
                'scan_status',
                EmailAttachmentScanStatus::Pending->value
            )
            ->whereNotNull('scan_started_at')
            ->where(
                'scan_started_at',
                '<=',
                $cutoff
            )
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $reset = 0;

        foreach ($ids as $attachmentId) {
            $attachmentId = (int) $attachmentId;

            $wasReset = DB::transaction(
                function () use (
                    $attachmentId,
                    $cutoff,
                ): bool {
                    $attachment = EmailAttachment::query()
                        ->lockForUpdate()
                        ->find($attachmentId);

                    if (
                        $attachment === null
                        || $attachment->scan_status
                        !== EmailAttachmentScanStatus::Pending
                        || $attachment->scan_started_at === null
                        || $attachment
                            ->scan_started_at
                            ->greaterThan($cutoff)
                    ) {
                        return false;
                    }

                    $attachment->forceFill([
                        'scan_started_at' => null,
                        'scan_failure_code' => 'stuck_scan_recovered',
                        'scan_failure_message' =>
                            'A stuck antivirus scan was reset by recovery.',
                    ])->save();

                    return true;
                },
                3,
            );

            if (!$wasReset) {
                continue;
            }

            $reset++;

            $this->dispatcher->releaseClaim(
                $attachmentId
            );

            $this->dispatcher->dispatch(
                $attachmentId
            );
        }

        return $reset;
    }

    private function dispatchPendingScans(
        int $limit
    ): int {
        $graceSeconds = max(
            0,
            (int) config(
                'simpledesk-mail-antivirus.recovery.grace_seconds',
                120
            )
        );

        $cutoff = now()->subSeconds(
            $graceSeconds
        );

        $ids = EmailAttachment::query()
            ->whereIn(
                'scan_status',
                [
                    EmailAttachmentScanStatus::NotScanned->value,
                    EmailAttachmentScanStatus::Pending->value,
                ]
            )
            ->whereNull('scan_started_at')
            ->where(
                'created_at',
                '<=',
                $cutoff
            )
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $dispatched = 0;

        foreach ($ids as $attachmentId) {
            $attachmentId = (int) $attachmentId;

            DB::transaction(
                function () use (
                    $attachmentId
                ): void {
                    $attachment = EmailAttachment::query()
                        ->lockForUpdate()
                        ->find($attachmentId);

                    if (
                        $attachment !== null
                        && $attachment->scan_status
                        === EmailAttachmentScanStatus::NotScanned
                    ) {
                        $attachment->forceFill([
                            'scan_status' => EmailAttachmentScanStatus::Pending,
                            'scan_started_at' => null,
                            'scanned_at' => null,
                            'scan_failure_code' => null,
                            'scan_failure_message' => null,
                        ])->save();
                    }
                },
                3,
            );

            if (
                $this->dispatcher->dispatch(
                    $attachmentId
                )
            ) {
                $dispatched++;
            }
        }

        return $dispatched;
    }
}

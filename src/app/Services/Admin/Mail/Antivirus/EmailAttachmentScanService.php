<?php

namespace App\Services\Admin\Mail\Antivirus;

use App\Contracts\Admin\Mail\Antivirus\AttachmentScanDriver;
use App\Data\Admin\Mail\AttachmentScanResultData;
use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Exceptions\Admin\Mail\AttachmentScanException;
use App\Models\Admin\Mail\EmailAttachment;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;

class EmailAttachmentScanService
{
    public function __construct(
        private readonly AttachmentScanDriver $driver,
        private readonly FilesystemFactory $filesystem,
        private readonly OutgoingAttachmentScanCompletionService $completion,
        private readonly int $processingLockSeconds,
        private readonly bool $verifyChecksums,
    ) {
    }

    public function scan(
        int $emailAttachmentId
    ): ?AttachmentScanResultData {
        $attachment = $this->claim(
            $emailAttachmentId
        );

        if ($attachment === null) {
            return null;
        }

        $storage = $this->filesystem->disk(
            $attachment->disk
        );

        $this->assertStoredFileIsValid(
            storage: $storage,
            attachment: $attachment,
        );

        $stream = $storage->readStream(
            $attachment->path
        );

        if ($stream === false) {
            throw new AttachmentScanException(
                message: "Attachment [{$attachment->file_name}] cannot be opened for antivirus scanning.",
                errorCode: 'attachment_scan_stream_open_failed',
                retryable: true,
                context: [
                    'attachment_id' => $attachment->id,
                    'disk' => $attachment->disk,
                    'path' => $attachment->path,
                ],
            );
        }

        try {
            $result = $this->driver->scanStream(
                stream: $stream,
                fileName: $attachment->file_name,
                expectedSize: $attachment->size,
            );
        } finally {
            fclose($stream);
        }

        $this->storeResult(
            attachmentId: $attachment->id,
            result: $result,
        );

        $this->completion->refresh(
            $attachment->email_message_id
        );

        return $result;
    }

    public function recordRetryableFailure(
        int $emailAttachmentId,
        AttachmentScanException $exception,
    ): void {
        $emailMessageId = DB::transaction(
            function () use (
                $emailAttachmentId,
                $exception,
            ): ?int {
                $attachment = EmailAttachment::query()
                    ->lockForUpdate()
                    ->find($emailAttachmentId);

                if ($attachment === null) {
                    return null;
                }

                if (
                    in_array(
                        $attachment->scan_status,
                        [
                            EmailAttachmentScanStatus::Clean,
                            EmailAttachmentScanStatus::Infected,
                        ],
                        true,
                    )
                ) {
                    return $attachment->email_message_id;
                }

                $attachment->forceFill([
                    'scan_status' => EmailAttachmentScanStatus::Pending,
                    'scan_started_at' => null,
                    'scanned_at' => null,
                    'scan_failure_code' => $exception->errorCode(),
                    'scan_failure_message' => mb_substr(
                        $exception->getMessage(),
                        0,
                        10000
                    ),
                    'scan_result' => $this->failureResult(
                        attachment: $attachment,
                        exception: $exception,
                        final: false,
                    ),
                ])->save();

                return $attachment->email_message_id;
            },
            3,
        );

        if ($emailMessageId !== null) {
            $this->completion->refresh(
                $emailMessageId
            );
        }
    }

    public function markFailed(
        int $emailAttachmentId,
        AttachmentScanException $exception,
    ): void {
        $emailMessageId = DB::transaction(
            function () use (
                $emailAttachmentId,
                $exception,
            ): ?int {
                $attachment = EmailAttachment::query()
                    ->lockForUpdate()
                    ->find($emailAttachmentId);

                if ($attachment === null) {
                    return null;
                }

                if (
                    in_array(
                        $attachment->scan_status,
                        [
                            EmailAttachmentScanStatus::Clean,
                            EmailAttachmentScanStatus::Infected,
                        ],
                        true,
                    )
                ) {
                    return $attachment->email_message_id;
                }

                $attachment->forceFill([
                    'scan_status' => EmailAttachmentScanStatus::Failed,
                    'scan_started_at' => null,
                    'scanned_at' => now(),
                    'scan_failure_code' => $exception->errorCode(),
                    'scan_failure_message' => mb_substr(
                        $exception->getMessage(),
                        0,
                        10000
                    ),
                    'scan_result' => $this->failureResult(
                        attachment: $attachment,
                        exception: $exception,
                        final: true,
                    ),
                ])->save();

                return $attachment->email_message_id;
            },
            3,
        );

        if ($emailMessageId !== null) {
            $this->completion->refresh(
                $emailMessageId
            );
        }
    }

    private function claim(
        int $emailAttachmentId
    ): ?EmailAttachment {
        return DB::transaction(
            function () use (
                $emailAttachmentId
            ): ?EmailAttachment {
                $attachment = EmailAttachment::query()
                    ->lockForUpdate()
                    ->find($emailAttachmentId);

                if ($attachment === null) {
                    return null;
                }

                if (
                    in_array(
                        $attachment->scan_status,
                        [
                            EmailAttachmentScanStatus::Clean,
                            EmailAttachmentScanStatus::Infected,
                        ],
                        true,
                    )
                ) {
                    return null;
                }

                if (
                    $attachment->scan_status
                    === EmailAttachmentScanStatus::Failed
                ) {
                    return null;
                }

                $threshold = now()->subSeconds(
                    $this->processingLockSeconds
                );

                if (
                    $attachment->scan_started_at !== null
                    && $attachment
                        ->scan_started_at
                        ->greaterThan($threshold)
                ) {
                    return null;
                }

                $attachment->forceFill([
                    'scan_status' => EmailAttachmentScanStatus::Pending,
                    'scan_started_at' => now(),
                    'scan_attempts' => $attachment->scan_attempts + 1,
                    'scanned_at' => null,
                    'scan_failure_code' => null,
                    'scan_failure_message' => null,
                ])->save();

                return $attachment->fresh();
            },
            3,
        );
    }

    private function assertStoredFileIsValid(
        Filesystem $storage,
        EmailAttachment $attachment,
    ): void {
        if (!$storage->exists($attachment->path)) {
            throw new AttachmentScanException(
                message: "Attachment file [{$attachment->path}] does not exist on disk [{$attachment->disk}].",
                errorCode: 'attachment_file_missing',
                retryable: false,
                context: [
                    'attachment_id' => $attachment->id,
                    'disk' => $attachment->disk,
                    'path' => $attachment->path,
                ],
            );
        }

        $storedSize = $storage->size(
            $attachment->path
        );

        if ($storedSize !== $attachment->size) {
            throw new AttachmentScanException(
                message: "Attachment [{$attachment->file_name}] size verification failed before antivirus scanning.",
                errorCode: 'attachment_size_verification_failed',
                retryable: false,
                context: [
                    'attachment_id' => $attachment->id,
                    'expected_size' => $attachment->size,
                    'stored_size' => $storedSize,
                ],
            );
        }

        if (!$this->verifyChecksums) {
            return;
        }

        $stream = $storage->readStream(
            $attachment->path
        );

        if ($stream === false) {
            throw new AttachmentScanException(
                message: "Attachment [{$attachment->file_name}] cannot be read for checksum verification.",
                errorCode: 'attachment_checksum_stream_failed',
                retryable: true,
                context: [
                    'attachment_id' => $attachment->id,
                ],
            );
        }

        try {
            $context = hash_init('sha256');

            hash_update_stream(
                $context,
                $stream
            );

            $checksum = hash_final(
                $context
            );
        } finally {
            fclose($stream);
        }

        if (
            !hash_equals(
                $attachment->checksum_sha256,
                $checksum
            )
        ) {
            throw new AttachmentScanException(
                message: "Attachment [{$attachment->file_name}] checksum verification failed before antivirus scanning.",
                errorCode: 'attachment_checksum_verification_failed',
                retryable: false,
                context: [
                    'attachment_id' => $attachment->id,
                ],
            );
        }
    }

    private function storeResult(
        int $attachmentId,
        AttachmentScanResultData $result,
    ): void {
        DB::transaction(
            function () use (
                $attachmentId,
                $result,
            ): void {
                $attachment = EmailAttachment::query()
                    ->lockForUpdate()
                    ->find($attachmentId);

                if ($attachment === null) {
                    return;
                }

                $attachment->forceFill([
                    'scan_status' => $result->clean
                        ? EmailAttachmentScanStatus::Clean
                        : EmailAttachmentScanStatus::Infected,
                    'scan_started_at' => null,
                    'scanned_at' => now(),
                    'quarantined_at' => $result->clean
                        ? null
                        : now(),
                    'scan_failure_code' => null,
                    'scan_failure_message' => null,
                    'scan_result' => [
                        'driver' => $result->driver,
                        'clean' => $result->clean,
                        'signature' => $result->signature,
                        'raw_response' => $result->rawResponse,
                        'scanned_bytes' => $result->scannedBytes,
                        'metadata' => $result->metadata,
                        'completed_at' => now()->toIso8601String(),
                    ],
                ])->save();
            },
            3,
        );
    }

    private function failureResult(
        EmailAttachment $attachment,
        AttachmentScanException $exception,
        bool $final,
    ): array {
        return [
            'driver' => $this->driver->name(),
            'clean' => null,
            'error_code' => $exception->errorCode(),
            'error_message' => $exception->getMessage(),
            'retryable' => $exception->retryable(),
            'final' => $final,
            'attempt' => $attachment->scan_attempts,
            'context' => $exception->context(),
            'recorded_at' => now()->toIso8601String(),
        ];
    }
}

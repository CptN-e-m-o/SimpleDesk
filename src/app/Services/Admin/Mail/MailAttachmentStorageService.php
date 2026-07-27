<?php

namespace App\Services\Admin\Mail;

use App\Data\Mail\MailAttachmentData;
use App\Enums\Mail\EmailAttachmentScanStatus;
use App\Exceptions\Admin\Mail\MailStorageException;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Throwable;

class MailAttachmentStorageService
{
    public function __construct(
        private readonly Factory $filesystem,
        private readonly string $disk,
        private readonly string $rootPath,
    ) {
    }

    public function store(
        EmailMessage $emailMessage,
        MailAttachmentData $attachment,
        int $position,
    ): EmailAttachment {
        [$checksum, $size] = $this->fileMetadata(
            $attachment
        );

        $deduplicationKey = hash(
            'sha256',
            implode('|', [
                "position:{$position}",
                "external:{$attachment->externalId}",
                "content-id:{$attachment->contentId}",
                "name:{$attachment->fileName}",
                "checksum:{$checksum}",
            ])
        );

        $existing = $emailMessage
            ->attachments()
            ->where(
                'deduplication_key',
                $deduplicationKey
            )
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $path = $this->makeStoragePath(
            emailMessage: $emailMessage,
            fileName: $attachment->fileName,
        );

        $storage = $this->filesystem->disk($this->disk);

        $this->writeAttachment(
            storage: $storage,
            path: $path,
            attachment: $attachment,
        );

        try {
            return $emailMessage->attachments()->create([
                'position' => $position,
                'external_id' => $attachment->externalId,
                'deduplication_key' => $deduplicationKey,
                'file_name' => $attachment->fileName,
                'mime_type' => $attachment->mimeType,
                'size' => $size,
                'disk' => $this->disk,
                'path' => $path,
                'checksum_sha256' => $checksum,
                'content_id' => $attachment->contentId,
                'is_inline' => $attachment->inline,
                'scan_status' =>
                    EmailAttachmentScanStatus::NotScanned,
                'metadata' => $attachment->metadata,
            ]);
        } catch (QueryException $exception) {
            $storage->delete($path);

            $existing = $emailMessage
                ->attachments()
                ->where(
                    'deduplication_key',
                    $deduplicationKey
                )
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        } catch (Throwable $exception) {
            $storage->delete($path);

            throw $exception;
        }
    }

    private function fileMetadata(
        MailAttachmentData $attachment
    ): array {
        if ($attachment->content !== null) {
            return [
                hash('sha256', $attachment->content),
                strlen($attachment->content),
            ];
        }

        if (
            $attachment->temporaryPath === null
            || !is_file($attachment->temporaryPath)
            || !is_readable($attachment->temporaryPath)
        ) {
            throw new MailStorageException(
                "Attachment [{$attachment->fileName}] "
                . 'has no readable local content.'
            );
        }

        $checksum = hash_file(
            'sha256',
            $attachment->temporaryPath
        );

        $size = filesize($attachment->temporaryPath);

        if ($checksum === false || $size === false) {
            throw new MailStorageException(
                "Unable to inspect attachment "
                . "[{$attachment->fileName}]."
            );
        }

        return [
            $checksum,
            $size,
        ];
    }

    private function writeAttachment(
        object $storage,
        string $path,
        MailAttachmentData $attachment,
    ): void {
        if ($attachment->content !== null) {
            if (!$storage->put($path, $attachment->content)) {
                throw new MailStorageException(
                    "Unable to store attachment "
                    . "[{$attachment->fileName}]."
                );
            }

            return;
        }

        $stream = fopen(
            $attachment->temporaryPath,
            'rb'
        );

        if ($stream === false) {
            throw new MailStorageException(
                "Unable to open attachment "
                . "[{$attachment->fileName}]."
            );
        }

        try {
            if (!$storage->writeStream($path, $stream)) {
                throw new MailStorageException(
                    "Unable to store attachment "
                    . "[{$attachment->fileName}]."
                );
            }
        } finally {
            fclose($stream);
        }
    }

    private function makeStoragePath(
        EmailMessage $emailMessage,
        string $fileName,
    ): string {
        $createdAt = $emailMessage->created_at ?? now();

        $extension = strtolower(
            (string) pathinfo(
                $fileName,
                PATHINFO_EXTENSION
            )
        );

        $extension = preg_replace(
            '/[^a-z0-9]+/',
            '',
            $extension
        );

        $storedFileName = (string) Str::uuid();

        if ($extension !== '') {
            $storedFileName .= ".{$extension}";
        }

        return implode('/', [
            trim($this->rootPath, '/'),
            $createdAt->format('Y'),
            $createdAt->format('m'),
            (string) $emailMessage->id,
            $storedFileName,
        ]);
    }
}

<?php

namespace App\Services\Admin\Mail;

use App\Exceptions\Admin\Mail\MailStorageException;
use App\Models\Admin\Mail\EmailAttachment;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MailAttachmentDownloadService
{
    /**
     * @param  array<int, string>  $allowedScanStatuses
     */
    public function __construct(
        private readonly FilesystemFactory $filesystem,
        private readonly array $allowedScanStatuses,
        private readonly bool $verifyChecksums,
    ) {}

    public function download(
        EmailAttachment $attachment
    ): StreamedResponse {
        $this->assertDownloadable(
            $attachment
        );

        $storage = $this->filesystem->disk(
            $attachment->disk
        );

        if (! $storage->exists($attachment->path)) {
            throw new MailStorageException(
                'Attachment file does not exist.'
            );
        }

        $size = $storage->size(
            $attachment->path
        );

        if ($size !== $attachment->size) {
            throw new MailStorageException(
                'Attachment size verification failed.'
            );
        }

        if ($this->verifyChecksums) {
            $this->verifyChecksum(
                storage: $storage,
                attachment: $attachment,
            );
        }

        $fileName = $this->safeFileName(
            $attachment->file_name
        );

        $mimeType = $this->safeMimeType(
            $attachment->mime_type
        );

        return response()->streamDownload(
            function () use (
                $storage,
                $attachment,
            ): void {
                $stream = $storage->readStream(
                    $attachment->path
                );

                if ($stream === false) {
                    throw new MailStorageException(
                        'Attachment cannot be opened for download.'
                    );
                }

                try {
                    fpassthru($stream);
                } finally {
                    fclose($stream);
                }
            },
            $fileName,
            [
                'Content-Type' => $mimeType,
                'Content-Length' => (string) $attachment->size,
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => 'sandbox',
                'Cross-Origin-Resource-Policy' => 'same-origin',
            ],
            'attachment',
        );
    }

    private function assertDownloadable(
        EmailAttachment $attachment
    ): void {
        if ($attachment->quarantined_at !== null) {
            throw new MailStorageException(
                'Attachment is quarantined.'
            );
        }

        $scanStatus = $attachment->scan_status;

        $status = is_string($scanStatus)
            ? $scanStatus
            : $scanStatus->value;

        $allowedScanStatuses = (bool) config(
            'simpledesk-mail-antivirus.enabled',
            false
        )
            ? [
                'clean',
            ]
            : $this->allowedScanStatuses;

        if (
            ! in_array(
                $status,
                $allowedScanStatuses,
                true
            )
        ) {
            throw new MailStorageException(
                'Attachment is not available for download.'
            );
        }
    }

    private function verifyChecksum(
        Filesystem $storage,
        EmailAttachment $attachment,
    ): void {
        $stream = $storage->readStream(
            $attachment->path
        );

        if ($stream === false) {
            throw new MailStorageException(
                'Attachment cannot be read.'
            );
        }

        try {
            $context = hash_init(
                'sha256'
            );

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
            ! hash_equals(
                $attachment->checksum_sha256,
                $checksum
            )
        ) {
            throw new MailStorageException(
                'Attachment checksum verification failed.'
            );
        }
    }

    private function safeFileName(
        string $fileName
    ): string {
        $fileName = basename(
            str_replace(
                '\\',
                '/',
                $fileName
            )
        );

        $fileName = preg_replace(
            '/[\x00-\x1F\x7F]+/u',
            ' ',
            $fileName
        );

        $fileName = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $fileName)
        );

        return $fileName !== ''
            ? mb_substr(
                $fileName,
                0,
                255
            )
            : 'attachment.bin';
    }

    private function safeMimeType(
        string $mimeType
    ): string {
        $mimeType = strtolower(
            trim(
                explode(
                    ';',
                    $mimeType,
                    2
                )[0]
            )
        );

        if (
            preg_match(
                '~^[a-z0-9!#$&^_.+\-]+/[a-z0-9!#$&^_.+\-]+$~',
                $mimeType
            ) !== 1
        ) {
            return 'application/octet-stream';
        }

        return $mimeType;
    }
}

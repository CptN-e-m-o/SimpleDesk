<?php

namespace App\Services\Admin\Mail;

use App\Data\Admin\Mail\MailAttachmentData;
use App\Exceptions\Admin\Mail\MailStorageException;

class OutgoingMailAttachmentValidator
{
    /**
     * @param  array<int, string>  $allowedMimeTypes
     */
    public function __construct(
        private readonly array $allowedMimeTypes,
        private readonly int $maxAttachmentCount,
        private readonly int $maxAttachmentBytes,
        private readonly int $maxTotalAttachmentBytes,
    ) {}

    /**
     * @param  array<int, MailAttachmentData>  $attachments
     */
    public function validate(array $attachments): void
    {
        if (count($attachments) > $this->maxAttachmentCount) {
            throw new MailStorageException(
                'Outgoing email contains too many attachments.'
            );
        }

        $totalSize = 0;

        foreach ($attachments as $attachment) {
            if (! $attachment instanceof MailAttachmentData) {
                throw new MailStorageException(
                    'Outgoing attachment must be an instance of '
                    .MailAttachmentData::class.'.'
                );
            }

            if (! $attachment->hasContent()) {
                throw new MailStorageException(
                    "Attachment [{$attachment->fileName}] has no content."
                );
            }

            $size = $this->attachmentSize($attachment);

            if ($size > $this->maxAttachmentBytes) {
                throw new MailStorageException(
                    "Attachment [{$attachment->fileName}] exceeds "
                    .'the configured size limit.'
                );
            }

            if (! $this->mimeTypeIsAllowed($attachment->mimeType)) {
                throw new MailStorageException(
                    "Attachment [{$attachment->fileName}] has "
                    ."a disallowed MIME type [{$attachment->mimeType}]."
                );
            }

            $totalSize += $size;

            if ($totalSize > $this->maxTotalAttachmentBytes) {
                throw new MailStorageException(
                    'Outgoing email attachments exceed '
                    .'the configured total size limit.'
                );
            }
        }
    }

    private function attachmentSize(
        MailAttachmentData $attachment
    ): int {
        if ($attachment->content !== null) {
            return strlen($attachment->content);
        }

        if (
            $attachment->temporaryPath === null
            || ! is_file($attachment->temporaryPath)
            || ! is_readable($attachment->temporaryPath)
        ) {
            throw new MailStorageException(
                "Attachment [{$attachment->fileName}] "
                .'has no readable local content.'
            );
        }

        $size = filesize($attachment->temporaryPath);

        if ($size === false) {
            throw new MailStorageException(
                "Unable to inspect attachment [{$attachment->fileName}]."
            );
        }

        return $size;
    }

    private function mimeTypeIsAllowed(
        string $mimeType
    ): bool {
        if ($this->allowedMimeTypes === []) {
            return true;
        }

        $mimeType = strtolower(
            trim(explode(';', $mimeType, 2)[0])
        );

        foreach ($this->allowedMimeTypes as $allowedMimeType) {
            $allowedMimeType = strtolower(
                trim($allowedMimeType)
            );

            if ($allowedMimeType === '') {
                continue;
            }

            if ($allowedMimeType === $mimeType) {
                return true;
            }

            if (
                str_ends_with($allowedMimeType, '/*')
                && str_starts_with(
                    $mimeType,
                    substr($allowedMimeType, 0, -1)
                )
            ) {
                return true;
            }
        }

        return false;
    }
}

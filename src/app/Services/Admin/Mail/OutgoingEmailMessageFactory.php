<?php

namespace App\Services\Admin\Mail;

use App\Data\Admin\Mail\MailAttachmentData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Exceptions\Admin\Mail\MailStorageException;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

class OutgoingEmailMessageFactory
{
    public function __construct(
        private readonly FilesystemFactory $filesystem,
        private readonly int $maxAttachmentBytes,
        private readonly int $maxTotalAttachmentBytes,
        private readonly bool $verifyChecksums,
    ) {
    }

    public function make(
        EmailMessage $emailMessage
    ): OutgoingEmailMessageData {
        $emailMessage->loadMissing('attachments');

        $attachments = [];
        $totalSize = 0;

        foreach (
            $emailMessage->attachments
            as $attachment
        ) {
            $this->assertAttachmentCanBeSent(
                $attachment
            );

            $totalSize += $attachment->size;

            if (
                $totalSize
                > $this->maxTotalAttachmentBytes
            ) {
                throw new MailStorageException(
                    'Outgoing email attachments exceed '
                    . 'the configured total size limit.'
                );
            }

            $contents = $this->readAttachment(
                $attachment
            );

            $attachments[] = new MailAttachmentData(
                fileName: $attachment->file_name,
                mimeType: $attachment->mime_type,
                size: $attachment->size,
                content: $contents,
                externalId: $attachment->external_id,
                contentId: $attachment->content_id,
                inline: $attachment->is_inline,
                metadata: $attachment->metadata ?? [],
            );
        }

        return OutgoingEmailMessageData::fromEmailMessage(
            message: $emailMessage,
            attachments: $attachments,
        );
    }

    private function assertAttachmentCanBeSent(
        EmailAttachment $attachment
    ): void {
        if (
            $attachment->size
            > $this->maxAttachmentBytes
        ) {
            throw new MailStorageException(
                "Attachment [{$attachment->file_name}] "
                . 'exceeds the configured size limit.'
            );
        }

        if (in_array(
            $attachment->scan_status,
            [
                EmailAttachmentScanStatus::Pending,
                EmailAttachmentScanStatus::Infected,
                EmailAttachmentScanStatus::Failed,
            ],
            true,
        )) {
            throw new MailStorageException(
                "Attachment [{$attachment->file_name}] "
                . 'cannot be sent because of its scan status.'
            );
        }

        if ($attachment->quarantined_at !== null) {
            throw new MailStorageException(
                "Attachment [{$attachment->file_name}] "
                . 'is quarantined.'
            );
        }
    }

    private function readAttachment(
        EmailAttachment $attachment
    ): string {
        $storage = $this->filesystem->disk(
            $attachment->disk
        );

        if (!$storage->exists($attachment->path)) {
            throw new MailStorageException(
                "Attachment file [{$attachment->path}] "
                . "does not exist on disk [{$attachment->disk}]."
            );
        }

        $contents = $storage->get(
            $attachment->path
        );

        if (
            strlen($contents)
            !== $attachment->size
        ) {
            throw new MailStorageException(
                "Attachment [{$attachment->file_name}] "
                . 'size does not match the stored metadata.'
            );
        }

        if (
            $this->verifyChecksums
            && !hash_equals(
                $attachment->checksum_sha256,
                hash('sha256', $contents)
            )
        ) {
            throw new MailStorageException(
                "Attachment [{$attachment->file_name}] "
                . 'checksum verification failed.'
            );
        }

        return $contents;
    }
}

<?php

namespace App\Services\Admin\Mail;

use App\Data\Admin\Mail\RejectedMailAttachmentData;
use App\Models\Admin\Mail\EmailAttachmentRejection;
use App\Models\Admin\Mail\EmailMessage;

class RejectedEmailAttachmentPersister
{
    /**
     * @param  array<int, RejectedMailAttachmentData>  $attachments
     */
    public function persist(
        EmailMessage $emailMessage,
        array $attachments,
    ): int {
        $stored = 0;

        foreach (
            array_values($attachments) as $position => $attachment
        ) {
            if (
                ! $attachment
                    instanceof RejectedMailAttachmentData
            ) {
                continue;
            }

            $deduplicationKey =
                $this->deduplicationKey(
                    attachment: $attachment,
                    position: $position,
                );

            EmailAttachmentRejection::query()
                ->updateOrCreate(
                    [
                        'email_message_id' => $emailMessage->id,

                        'deduplication_key' => $deduplicationKey,
                    ],
                    [
                        'position' => $position,

                        'external_id' => $attachment->externalId,

                        'file_name' => mb_substr(
                            $attachment->fileName,
                            0,
                            255
                        ),

                        'mime_type' => mb_substr(
                            $attachment->mimeType,
                            0,
                            255
                        ),

                        'reported_size' => $attachment->reportedSize,

                        'content_id' => $attachment->contentId,

                        'is_inline' => $attachment->inline,

                        'reason_code' => mb_substr(
                            $attachment->reasonCode,
                            0,
                            100
                        ),

                        'reason_message' => $attachment->reasonMessage,

                        'metadata' => $attachment->metadata,
                    ]
                );

            $stored++;
        }

        return $stored;
    }

    private function deduplicationKey(
        RejectedMailAttachmentData $attachment,
        int $position,
    ): string {
        return hash(
            'sha256',
            implode('|', [
                (string) $position,
                $attachment->externalId ?? '',
                $attachment->contentId ?? '',
                $attachment->fileName,
                $attachment->mimeType,
                (string) (
                    $attachment->reportedSize
                    ?? 0
                ),
                $attachment->reasonCode,
            ])
        );
    }
}

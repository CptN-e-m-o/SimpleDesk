<?php

namespace App\Data\Admin\Mail;

use InvalidArgumentException;

final readonly class RejectedMailAttachmentData
{
    public function __construct(
        public string $fileName,
        public string $mimeType,
        public ?int $reportedSize,
        public string $reasonCode,
        public string $reasonMessage,
        public ?string $externalId = null,
        public ?string $contentId = null,
        public bool $inline = false,
        public array $metadata = [],
    ) {
        if (trim($fileName) === '') {
            throw new InvalidArgumentException(
                'Rejected attachment file name cannot be empty.'
            );
        }

        if (trim($mimeType) === '') {
            throw new InvalidArgumentException(
                'Rejected attachment MIME type cannot be empty.'
            );
        }

        if (trim($reasonCode) === '') {
            throw new InvalidArgumentException(
                'Rejected attachment reason code cannot be empty.'
            );
        }
    }
}

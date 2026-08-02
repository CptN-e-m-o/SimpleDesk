<?php

namespace App\Data\Admin\Mail;

use RuntimeException;

final readonly class MailAttachmentData
{
    public function __construct(
        public string $fileName,
        public string $mimeType,
        public int $size = 0,
        public ?string $content = null,
        public ?string $temporaryPath = null,
        public ?string $externalId = null,
        public ?string $contentId = null,
        public bool $inline = false,
        public array $metadata = [],
    ) {}

    public function hasContent(): bool
    {
        return $this->content !== null
            || $this->temporaryPath !== null;
    }

    public function contents(): string
    {
        if ($this->content !== null) {
            return $this->content;
        }

        if ($this->temporaryPath === null) {
            throw new RuntimeException(
                "Attachment {$this->fileName} has no local content."
            );
        }

        $contents = file_get_contents($this->temporaryPath);

        if ($contents === false) {
            throw new RuntimeException(
                "Unable to read attachment {$this->fileName}."
            );
        }

        return $contents;
    }
}

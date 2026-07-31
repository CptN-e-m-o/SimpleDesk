<?php

namespace App\Data\Admin\Mail;

final readonly class ParsedInboundEmailContentData
{
    public function __construct(
        public string $body,
        public string $source,
        public bool $quotedTextRemoved,
        public bool $signatureRemoved,
        public int $originalLength,
        public int $parsedLength,
    ) {}
}

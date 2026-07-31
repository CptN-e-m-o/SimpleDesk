<?php

namespace App\Data\Admin\Mail;

final readonly class IncomingCursorData
{
    public function __construct(
        public int $mailboxChannelId,
        public string $value,
        public array $metadata = [],
    ) {}
}

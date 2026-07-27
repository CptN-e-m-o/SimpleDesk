<?php

namespace App\Data\Admin\Mail;

final readonly class TicketEmailThreadData
{
    /**
     * @param array<int, string> $references
     */
    public function __construct(
        public ?int $parentEmailMessageId,
        public ?string $inReplyToMessageId,
        public array $references,
    ) {
    }
}

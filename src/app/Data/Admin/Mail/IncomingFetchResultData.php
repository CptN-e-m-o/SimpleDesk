<?php

namespace App\Data\Admin\Mail;

use App\Enums\Admin\Mail\MailboxDriver;
use InvalidArgumentException;

final readonly class IncomingFetchResultData
{
    /**
     * @param array<int, NormalizedInboundMessageData> $messages
     */
    public function __construct(
        public array $messages,
        public ?string $nextCursor = null,
        public bool $hasMore = false,
        public array $metadata = [],
        public ?int $mailboxChannelId = null,
        public ?MailboxDriver $driver = null,
    ) {
        foreach ($messages as $message) {
            if (!$message instanceof NormalizedInboundMessageData) {
                throw new InvalidArgumentException(
                    'Fetched message must be normalized.'
                );
            }
        }
    }

    public function withSource(
        int $mailboxChannelId,
        MailboxDriver $driver,
    ): self {
        return new self(
            messages: $this->messages,
            nextCursor: $this->nextCursor,
            hasMore: $this->hasMore,
            metadata: $this->metadata,
            mailboxChannelId: $mailboxChannelId,
            driver: $driver,
        );
    }
}

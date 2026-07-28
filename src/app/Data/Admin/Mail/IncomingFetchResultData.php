<?php

namespace App\Data\Admin\Mail;

use App\Enums\Admin\Mail\MailboxDriver;
use InvalidArgumentException;

final readonly class IncomingFetchResultData
{
    /**
     * @param array<int, NormalizedInboundMessageData> $messages
     * @param array<int, FailedInboundMessageData> $failures
     */
    public function __construct(
        public array $messages,
        public ?string $nextCursor = null,
        public bool $hasMore = false,
        public array $metadata = [],
        public ?int $mailboxChannelId = null,
        public ?MailboxDriver $driver = null,
        public array $failures = [],
    ) {
        foreach ($messages as $message) {
            if (
                !$message
                    instanceof NormalizedInboundMessageData
            ) {
                throw new InvalidArgumentException(
                    'Fetched message must be normalized.'
                );
            }
        }

        foreach ($failures as $failure) {
            if (
                !$failure
                    instanceof FailedInboundMessageData
            ) {
                throw new InvalidArgumentException(
                    'Fetch failure must be an instance '
                    . 'of FailedInboundMessageData.'
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
            failures: $this->failures,
        );
    }
}

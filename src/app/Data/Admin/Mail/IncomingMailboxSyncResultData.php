<?php

namespace App\Data\Admin\Mail;

use App\Enums\Admin\Mail\MailboxDriver;

final readonly class IncomingMailboxSyncResultData
{
    public function __construct(
        public int $mailboxId,
        public int $mailboxChannelId,
        public MailboxDriver $driver,
        public int $pages,
        public int $fetched,
        public int $stored,
        public int $duplicates,
        public int $acknowledged,
        public bool $truncated,
        public ?string $nextCursor,
    ) {
    }
}

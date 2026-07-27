<?php

namespace App\Data\Mail;

use DateTimeImmutable;

final readonly class OutgoingSendResultData
{
    /**
     * @param array<int, MailAddressData> $acceptedRecipients
     * @param array<int, MailAddressData> $rejectedRecipients
     */
    public function __construct(
        public ?string $externalMessageId,
        public ?string $internetMessageId,
        public array $acceptedRecipients,
        public array $rejectedRecipients,
        public DateTimeImmutable $sentAt,
        public array $providerResponse = [],
        public array $metadata = [],
    ) {
    }
}

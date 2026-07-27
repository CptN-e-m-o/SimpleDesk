<?php

namespace App\Data\Mail;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class NormalizedInboundMessageData
{
    /**
     * @param array<int, MailAddressData> $to
     * @param array<int, MailAddressData> $cc
     * @param array<int, MailAddressData> $bcc
     * @param array<int, MailAddressData> $replyTo
     * @param array<int, string> $references
     * @param array<int, MailAttachmentData> $attachments
     */
    public function __construct(
        public string $externalMessageId,
        public ?string $internetMessageId,
        public MailAddressData $from,
        public array $to,
        public array $cc,
        public array $bcc,
        public array $replyTo,
        public ?string $subject,
        public ?string $textBody,
        public ?string $htmlBody,
        public array $headers,
        public array $attachments,
        public DateTimeImmutable $receivedAt,
        public ?string $inReplyToMessageId = null,
        public array $references = [],
        public array $metadata = [],
        public ?string $rawMessage = null,
    ) {
        if (trim($externalMessageId) === '') {
            throw new InvalidArgumentException(
                'External message ID cannot be empty.'
            );
        }

        $this->assertAddresses($to);
        $this->assertAddresses($cc);
        $this->assertAddresses($bcc);
        $this->assertAddresses($replyTo);
        $this->assertAttachments($attachments);
    }

    private function assertAddresses(array $addresses): void
    {
        foreach ($addresses as $address) {
            if (!$address instanceof MailAddressData) {
                throw new InvalidArgumentException(
                    'Recipient must be an instance of MailAddressData.'
                );
            }
        }
    }

    private function assertAttachments(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if (!$attachment instanceof MailAttachmentData) {
                throw new InvalidArgumentException(
                    'Attachment must be an instance of MailAttachmentData.'
                );
            }
        }
    }
}

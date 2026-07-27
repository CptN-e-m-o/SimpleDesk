<?php

namespace App\Data\Mail;

use App\Models\Admin\Mail\EmailMessage;
use InvalidArgumentException;

final readonly class OutgoingEmailMessageData
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
        public string $idempotencyKey,
        public ?MailAddressData $from,
        public array $to,
        public array $cc,
        public array $bcc,
        public array $replyTo,
        public string $subject,
        public ?string $textBody,
        public ?string $htmlBody,
        public array $headers = [],
        public array $attachments = [],
        public ?string $inReplyToMessageId = null,
        public array $references = [],
        public array $metadata = [],
    ) {
        if ($to === [] && $cc === [] && $bcc === []) {
            throw new InvalidArgumentException(
                'Outgoing message must have at least one recipient.'
            );
        }

        if (trim($idempotencyKey) === '') {
            throw new InvalidArgumentException(
                'Outgoing message must have an idempotency key.'
            );
        }
    }

    public static function fromEmailMessage(
        EmailMessage $message
    ): self {
        $from = null;

        if ($message->sender_address !== null) {
            $from = new MailAddressData(
                address: $message->sender_address,
                name: $message->sender_name,
            );
        }

        return new self(
            idempotencyKey: $message->idempotency_key,
            from: $from,
            to: MailAddressData::collection(
                $message->to_recipients ?? []
            ),
            cc: MailAddressData::collection(
                $message->cc_recipients ?? []
            ),
            bcc: MailAddressData::collection(
                $message->bcc_recipients ?? []
            ),
            replyTo: MailAddressData::collection(
                $message->reply_to_recipients ?? []
            ),
            subject: $message->subject ?? '',
            textBody: $message->text_body,
            htmlBody: $message->html_body,
            headers: $message->headers ?? [],
            attachments: [],
            inReplyToMessageId: $message->in_reply_to_message_id,
            references: $message->reference_message_ids ?? [],
            metadata: $message->metadata ?? [],
        );
    }
}

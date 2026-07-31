<?php

namespace App\Services\Admin\Mail\Drivers\Smtp;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\MailAttachmentData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Exceptions\Admin\Mail\MailDriverException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class SymfonyEmailFactory
{
    private const RESERVED_HEADERS = [
        'bcc',
        'cc',
        'content-transfer-encoding',
        'content-type',
        'date',
        'from',
        'in-reply-to',
        'message-id',
        'mime-version',
        'reply-to',
        'return-path',
        'sender',
        'subject',
        'to',
        'references',
    ];

    public function make(
        OutgoingEmailMessageData $message
    ): Email {
        if ($message->from === null) {
            throw $this->invalidMessage(
                'Outgoing email has no sender.'
            );
        }

        $email = new Email;

        $email->from(
            $this->address($message->from)
        );

        if ($message->to !== []) {
            $email->to(
                ...$this->addresses($message->to)
            );
        }

        if ($message->cc !== []) {
            $email->cc(
                ...$this->addresses($message->cc)
            );
        }

        if ($message->bcc !== []) {
            $email->bcc(
                ...$this->addresses($message->bcc)
            );
        }

        if ($message->replyTo !== []) {
            $email->replyTo(
                ...$this->addresses($message->replyTo)
            );
        }

        $email->subject($message->subject);

        if ($message->textBody !== null) {
            $email->text($message->textBody);
        }

        $htmlBody = $message->htmlBody;

        $inlineContentIds = $this->inlineContentIds(
            $message->attachments
        );

        if ($htmlBody !== null) {
            foreach (
                $inlineContentIds as $originalContentId => $normalizedContentId
            ) {
                if (
                    $originalContentId
                    === $normalizedContentId
                ) {
                    continue;
                }

                $htmlBody = str_replace(
                    'cid:'.$originalContentId,
                    'cid:'.$normalizedContentId,
                    $htmlBody,
                );
            }

            $email->html($htmlBody);
        }

        if (
            $message->textBody === null
            && $htmlBody === null
            && $message->attachments === []
        ) {
            $email->text('');
        }

        $headers = $email->getHeaders();

        if ($message->internetMessageId !== null) {
            $headers->addIdHeader(
                'Message-ID',
                $this->normalizeMessageId(
                    $message->internetMessageId
                )
            );
        }

        if ($message->inReplyToMessageId !== null) {
            $headers->addIdHeader(
                'In-Reply-To',
                $this->normalizeMessageId(
                    $message->inReplyToMessageId
                )
            );
        }

        if ($message->references !== []) {
            $references = array_values(
                array_filter(
                    array_map(
                        fn (mixed $reference): ?string => $this->normalizeNullableMessageId(
                            $reference
                        ),
                        $message->references,
                    )
                )
            );

            if ($references !== []) {
                $headers->addIdHeader(
                    'References',
                    $references
                );
            }
        }

        $this->applyCustomHeaders(
            email: $email,
            customHeaders: $message->headers,
        );

        foreach ($message->attachments as $attachment) {
            $this->attach(
                email: $email,
                attachment: $attachment,
                normalizedContentId: $attachment->contentId !== null
                    ? (
                        $inlineContentIds[
                        $attachment->contentId
                        ] ?? null
                    )
                    : null,
            );
        }

        return $email;
    }

    private function attach(
        Email $email,
        MailAttachmentData $attachment,
        ?string $normalizedContentId,
    ): void {
        $part = new DataPart(
            body: $attachment->contents(),
            filename: $attachment->fileName,
            contentType: $attachment->mimeType,
        );

        if ($attachment->inline) {
            $part->asInline();

            if ($normalizedContentId !== null) {
                $part->setContentId(
                    $normalizedContentId
                );
            }
        }

        $email->addPart($part);
    }

    /**
     * @param  array<int, MailAttachmentData>  $attachments
     */
    private function inlineContentIds(
        array $attachments
    ): array {
        $contentIds = [];

        foreach ($attachments as $attachment) {
            if (
                ! $attachment instanceof MailAttachmentData
                || ! $attachment->inline
                || $attachment->contentId === null
            ) {
                continue;
            }

            $contentIds[$attachment->contentId] =
                $this->normalizeContentId(
                    $attachment->contentId
                );
        }

        return $contentIds;
    }

    private function applyCustomHeaders(
        Email $email,
        array $customHeaders,
    ): void {
        foreach ($customHeaders as $name => $values) {
            if (! is_string($name)) {
                throw $this->invalidMessage(
                    'Email header name must be a string.'
                );
            }

            $normalizedName = strtolower(
                trim($name)
            );

            if (
                $normalizedName === ''
                || preg_match(
                    '/^[a-z0-9][a-z0-9-]*$/i',
                    $name
                ) !== 1
            ) {
                throw $this->invalidMessage(
                    "Invalid email header name [{$name}]."
                );
            }

            if (in_array(
                $normalizedName,
                self::RESERVED_HEADERS,
                true,
            )) {
                continue;
            }

            $values = is_array($values)
                ? $values
                : [$values];

            foreach ($values as $value) {
                if (! is_scalar($value)) {
                    throw $this->invalidMessage(
                        "Email header [{$name}] must be scalar."
                    );
                }

                $value = (string) $value;

                if (
                    str_contains($value, "\r")
                    || str_contains($value, "\n")
                ) {
                    throw $this->invalidMessage(
                        "Email header [{$name}] contains a line break."
                    );
                }

                $email
                    ->getHeaders()
                    ->addTextHeader(
                        $name,
                        $value,
                    );
            }
        }
    }

    private function address(
        MailAddressData $address
    ): Address {
        return new Address(
            address: $address->address,
            name: $address->name ?? '',
        );
    }

    /**
     * @param  array<int, MailAddressData>  $addresses
     * @return array<int, Address>
     */
    private function addresses(
        array $addresses
    ): array {
        return array_map(
            fn (MailAddressData $address): Address => $this->address($address),
            $addresses,
        );
    }

    private function normalizeMessageId(
        string $messageId
    ): string {
        $messageId = trim(
            $messageId,
            " \t\n\r\0\x0B<>"
        );

        if ($messageId === '') {
            throw $this->invalidMessage(
                'Email Message-ID cannot be empty.'
            );
        }

        return $messageId;
    }

    private function normalizeNullableMessageId(
        mixed $messageId
    ): ?string {
        if (! is_scalar($messageId)) {
            return null;
        }

        $messageId = trim(
            (string) $messageId,
            " \t\n\r\0\x0B<>"
        );

        return $messageId !== ''
            ? $messageId
            : null;
    }

    private function normalizeContentId(
        string $contentId
    ): string {
        $contentId = trim(
            $contentId,
            " \t\n\r\0\x0B<>"
        );

        if ($contentId === '') {
            throw $this->invalidMessage(
                'Inline attachment Content-ID cannot be empty.'
            );
        }

        if (! str_contains($contentId, '@')) {
            $contentId .= '@simpledesk.local';
        }

        return $contentId;
    }

    private function invalidMessage(
        string $message
    ): MailDriverException {
        return new MailDriverException(
            message: $message,
            driverErrorCode: 'smtp_invalid_message',
            retryable: false,
            failoverAllowed: false,
            affectsChannelHealth: false,
        );
    }
}

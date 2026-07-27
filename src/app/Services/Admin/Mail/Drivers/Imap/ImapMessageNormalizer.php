<?php

namespace App\Services\Admin\Mail\Drivers\Imap;

use App\Data\Admin\Mail\ImapChannelConfigurationData;
use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\MailAttachmentData;
use App\Data\Admin\Mail\NormalizedInboundMessageData;
use App\Exceptions\Admin\Mail\MailDriverException;
use DateTimeImmutable;
use DateTimeInterface;
use Throwable;
use Webklex\PHPIMAP\Address;
use Webklex\PHPIMAP\Attribute;
use Webklex\PHPIMAP\Attachment;
use Webklex\PHPIMAP\Message;

class ImapMessageNormalizer
{
    public function normalize(
        Message $message,
        ImapChannelConfigurationData $configuration,
        int $uidValidity,
    ): NormalizedInboundMessageData {
        $uid = (int) $message->getUid();

        if ($uid < 1) {
            throw $this->invalidMessage(
                'IMAP message has no valid UID.'
            );
        }

        $textBody = $this->nullableBody(
            $message->getTextBody()
        );

        $htmlBody = $this->nullableBody(
            $message->getHTMLBody()
        );

        $fromAddresses = $this->addresses(
            $message->getFrom()
        );

        $from = $fromAddresses[0]
            ?? new MailAddressData(
                address: 'unknown@invalid.local',
                name: 'Unknown sender',
            );

        $internetMessageId = $this->normalizeMessageId(
            $this->attributeFirst(
                $message->getMessageId()
            )
        );

        $inReplyTo = $this->normalizeMessageId(
            $this->attributeFirst(
                $message->getInReplyTo()
            )
        );

        $references = $this->references(
            $message->getReferences()
        );

        $rawHeader = $message->getHeader()->raw;

        [
            $rawMessage,
            $rawMessageOmitted,
        ] = $this->rawMessage(
            message: $message,
            rawHeader: $rawHeader,
            configuration: $configuration,
        );

        $attachments = $this->attachments(
            message: $message,
            htmlBody: $htmlBody,
            configuration: $configuration,
        );

        return new NormalizedInboundMessageData(
            externalMessageId: implode(':', [
                'imap',
                $configuration->folder,
                $uidValidity,
                $uid,
            ]),
            internetMessageId: $internetMessageId,
            from: $from,
            to: $this->addresses(
                $message->getTo()
            ),
            cc: $this->addresses(
                $message->getCc()
            ),
            bcc: $this->addresses(
                $message->getBcc()
            ),
            replyTo: $this->addresses(
                $message->getReplyTo()
            ),
            subject: $this->nullableString(
                $this->attributeFirst(
                    $message->getSubject()
                )
            ),
            textBody: $textBody,
            htmlBody: $htmlBody,
            headers: $this->parseHeaders(
                $rawHeader
            ),
            attachments: $attachments,
            receivedAt: $this->receivedAt(
                $message
            ),
            inReplyToMessageId: $inReplyTo,
            references: $references,
            metadata: [
                'imap_uid' => $uid,
                'imap_uidvalidity' => $uidValidity,
                'imap_folder' => $configuration->folder,
                'imap_message_number' =>
                    (int) $message->getMsgn(),
                'imap_size' =>
                    (int) $message->getSize(),
                'imap_flags' =>
                    $this->flags($message),
                'raw_message_omitted' =>
                    $rawMessageOmitted,
            ],
            rawMessage: $rawMessage,
        );
    }

    /**
     * @return array<int, MailAddressData>
     */
    private function addresses(
        Attribute $attribute
    ): array {
        $addresses = [];

        foreach ($attribute->toArray() as $value) {
            if ($value instanceof Address) {
                $email = trim($value->mail);

                if ($email === '') {
                    continue;
                }

                $addresses[] = new MailAddressData(
                    address: $email,
                    name: trim($value->personal) !== ''
                        ? trim($value->personal)
                        : null,
                );

                continue;
            }

            if (!is_scalar($value)) {
                continue;
            }

            $email = trim((string) $value);

            if (
                $email === ''
                || filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                ) === false
            ) {
                continue;
            }

            $addresses[] = new MailAddressData(
                address: $email,
            );
        }

        return $addresses;
    }

    /**
     * @return array<int, MailAttachmentData>
     */
    private function attachments(
        Message $message,
        ?string $htmlBody,
        ImapChannelConfigurationData $configuration,
    ): array {
        $result = [];

        foreach (
            $message->getAttachments()
            as $attachment
        ) {
            if (!$attachment instanceof Attachment) {
                continue;
            }

            $content = $attachment->getContent();

            $size = strlen($content);

            if (
                $size
                > $configuration->maxAttachmentBytes
            ) {
                $name = $this->attachmentName(
                    $attachment
                );

                throw new MailDriverException(
                    message:
                    "IMAP attachment [{$name}] exceeds "
                    . 'the configured size limit.',
                    driverErrorCode:
                    'imap_attachment_too_large',
                    retryable: false,
                    failoverAllowed: false,
                    affectsChannelHealth: false,
                    context: [
                        'attachment_size' => $size,
                        'attachment_limit' =>
                            $configuration
                                ->maxAttachmentBytes,
                    ],
                );
            }

            $contentId = $this->normalizeContentId(
                $attachment->getId()
            );

            $disposition = strtolower(
                trim(
                    (string) $attachment->getDisposition()
                )
            );

            $inline = $disposition === 'inline';

            if (
                !$inline
                && $contentId !== null
                && $htmlBody !== null
                && str_contains(
                    $htmlBody,
                    'cid:' . $contentId
                )
            ) {
                $inline = true;
            }

            $mimeType = trim(
                (string) $attachment->getContentType()
            );

            if ($mimeType === '') {
                $mimeType =
                    $attachment->getMimeType()
                    ?? 'application/octet-stream';
            }

            $externalId = trim(
                (string) $attachment->getHash()
            );

            if ($externalId === '') {
                $externalId =
                    'part:'
                    . (string) $attachment
                        ->getPartNumber();
            }

            $result[] = new MailAttachmentData(
                fileName:
                $this->attachmentName(
                    $attachment
                ),
                mimeType: $mimeType,
                size: $size,
                content: $content,
                externalId: $externalId,
                contentId: $inline
                    ? $contentId
                    : null,
                inline: $inline,
                metadata: [
                    'imap_part_number' =>
                        $attachment->getPartNumber(),
                    'imap_disposition' =>
                        $disposition !== ''
                            ? $disposition
                            : null,
                    'imap_reported_size' =>
                        (int) $attachment->getSize(),
                ],
            );
        }

        return $result;
    }

    private function attachmentName(
        Attachment $attachment
    ): string {
        $name = trim(
            (string) $attachment->getName()
        );

        if ($name === '') {
            $name = 'attachment-'
                . (string) $attachment
                    ->getPartNumber();
        }

        $name = str_replace(
            '\\',
            '/',
            $name
        );

        $name = basename($name);

        $name = preg_replace(
            '/[\x00-\x1F\x7F]/u',
            '',
            $name
        );

        $name = trim((string) $name);

        return $name !== ''
            ? $name
            : 'attachment.bin';
    }

    private function rawMessage(
        Message $message,
        string $rawHeader,
        ImapChannelConfigurationData $configuration,
    ): array {
        if (!$configuration->storeRawMessage) {
            return [
                null,
                true,
            ];
        }

        $rawBody = $message->getRawBody();

        $rawMessage = rtrim(
                $rawHeader,
                "\r\n"
            )
            . "\r\n\r\n"
            . $rawBody;

        if (
            strlen($rawMessage)
            > $configuration->maxRawMessageBytes
        ) {
            return [
                null,
                true,
            ];
        }

        return [
            $rawMessage,
            false,
        ];
    }

    private function receivedAt(
        Message $message
    ): DateTimeImmutable {
        $value = $message->getDate()->first();

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface(
                $value
            );
        }

        if (is_scalar($value)) {
            try {
                return new DateTimeImmutable(
                    (string) $value
                );
            } catch (Throwable) {
                //
            }
        }

        return new DateTimeImmutable();
    }

    /**
     * @return array<int, string>
     */
    private function references(
        Attribute $attribute
    ): array {
        $references = [];

        foreach ($attribute->toArray() as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            preg_match_all(
                '/<([^>]+)>/',
                $value,
                $matches
            );

            if (($matches[1] ?? []) !== []) {
                foreach ($matches[1] as $messageId) {
                    $messageId = trim($messageId);

                    if ($messageId !== '') {
                        $references[] = $messageId;
                    }
                }

                continue;
            }

            foreach (
                preg_split(
                    '/\s+/',
                    $value
                ) ?: []
                as $messageId
            ) {
                $messageId = trim(
                    $messageId,
                    " \t\n\r\0\x0B<>"
                );

                if ($messageId !== '') {
                    $references[] = $messageId;
                }
            }
        }

        return array_values(
            array_unique($references)
        );
    }

    private function parseHeaders(
        string $rawHeader
    ): array {
        $headers = [];

        $unfolded = preg_replace(
            "/\r?\n[ \t]+/",
            ' ',
            $rawHeader
        );

        foreach (
            preg_split(
                "/\r?\n/",
                (string) $unfolded
            ) ?: []
            as $line
        ) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(
                ':',
                $line,
                2
            );

            $name = strtolower(
                trim($name)
            );

            $value = trim($value);

            if ($name === '') {
                continue;
            }

            $headers[$name] ??= [];
            $headers[$name][] = $value;
        }

        return $headers;
    }

    private function flags(
        Message $message
    ): array {
        $flags = [];

        foreach (
            $message->getFlags()->toArray()
            as $flag
        ) {
            if (is_scalar($flag)) {
                $flags[] = (string) $flag;
                continue;
            }

            if (
                is_object($flag)
                && method_exists(
                    $flag,
                    '__toString'
                )
            ) {
                $flags[] = (string) $flag;
            }
        }

        return array_values(
            array_unique($flags)
        );
    }

    private function attributeFirst(
        Attribute $attribute
    ): mixed {
        return $attribute->first();
    }

    private function nullableBody(
        string $body
    ): ?string {
        return $body !== ''
            ? $body
            : null;
    }

    private function nullableString(
        mixed $value
    ): ?string {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== ''
            ? $value
            : null;
    }

    private function normalizeMessageId(
        mixed $messageId
    ): ?string {
        if (!is_scalar($messageId)) {
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
        mixed $contentId
    ): ?string {
        if (!is_scalar($contentId)) {
            return null;
        }

        $contentId = trim(
            (string) $contentId,
            " \t\n\r\0\x0B<>"
        );

        return $contentId !== ''
            ? $contentId
            : null;
    }

    private function invalidMessage(
        string $message
    ): MailDriverException {
        return new MailDriverException(
            message: $message,
            driverErrorCode: 'imap_invalid_message',
            retryable: false,
            failoverAllowed: false,
            affectsChannelHealth: false,
        );
    }
}

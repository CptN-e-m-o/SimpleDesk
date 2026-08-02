<?php

namespace App\Services\Admin\Mail\Drivers\Imap;

use App\Data\Admin\Mail\FailedInboundMessageData;
use App\Data\Admin\Mail\ImapChannelConfigurationData;
use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\MailAttachmentData;
use App\Data\Admin\Mail\NormalizedInboundMessageData;
use App\Data\Admin\Mail\RejectedMailAttachmentData;
use App\Exceptions\Admin\Mail\MailDriverException;
use DateTimeImmutable;
use DateTimeInterface;
use Throwable;
use Webklex\PHPIMAP\Address;
use Webklex\PHPIMAP\Attachment;
use Webklex\PHPIMAP\Attribute;
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

        $internetMessageId =
            $this->normalizeMessageId(
                $this->attributeFirst(
                    $message->getMessageId()
                )
            );

        $inReplyTo =
            $this->normalizeMessageId(
                $this->attributeFirst(
                    $message->getInReplyTo()
                )
            );

        $references = $this->references(
            $message->getReferences()
        );

        $rawHeader =
            $message->getHeader()->raw;

        [
            $rawMessage,
            $rawMessageOmitted,
            $rawMessageOmissionReason,
        ] = $this->rawMessage(
            message: $message,
            rawHeader: $rawHeader,
            configuration: $configuration,
        );

        [
            $attachments,
            $rejectedAttachments,
        ] = $this->attachments(
            message: $message,
            htmlBody: $htmlBody,
            configuration: $configuration,
        );

        return new NormalizedInboundMessageData(
            externalMessageId: $this->externalMessageId(
                folder: $configuration->folder,

                uidValidity: $uidValidity,

                uid: $uid,
            ),

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

                'imap_message_number' => (int) $message->getMsgn(),

                'imap_size' => (int) $message->getSize(),

                'imap_flags' => $this->flags($message),

                'raw_message_omitted' => $rawMessageOmitted,

                'raw_message_omission_reason' => $rawMessageOmissionReason,

                'attachment_count' => count($attachments),

                'rejected_attachment_count' => count(
                    $rejectedAttachments
                ),
            ],

            rawMessage: $rawMessage,

            rejectedAttachments: $rejectedAttachments,
        );
    }

    public function failed(
        Message $message,
        ImapChannelConfigurationData $configuration,
        int $uidValidity,
        Throwable $exception,
    ): FailedInboundMessageData {
        $uid = $this->safeInteger(
            static fn (): mixed => $message->getUid()
        );

        if ($uid < 1) {
            throw $this->invalidMessage(
                'Unable to quarantine IMAP message '
                .'because its UID is unavailable.'
            );
        }

        $rawHeader =
            $this->safeRawHeader(
                $message
            );

        [
            $rawMessage,
            $rawMessageOmitted,
            $rawMessageOmissionReason,
        ] = $this->rawMessage(
            message: $message,
            rawHeader: $rawHeader,
            configuration: $configuration,
        );

        $errorCode =
            $exception
            instanceof MailDriverException
                ? $exception
                    ->driverErrorCode()
                : 'imap_message_normalization_failed';

        $retryable =
            $exception
            instanceof MailDriverException
                ? $exception->retryable()
                : false;

        $context =
            $exception
            instanceof MailDriverException
                ? $exception->context()
                : [];

        $acknowledgementMessage =
            new NormalizedInboundMessageData(
                externalMessageId: $this->externalMessageId(
                    folder: $configuration->folder,

                    uidValidity: $uidValidity,

                    uid: $uid,
                ),

                internetMessageId: null,

                from: new MailAddressData(
                    address: 'unknown@invalid.local',

                    name: 'Unparsed IMAP sender',
                ),

                to: [],
                cc: [],
                bcc: [],
                replyTo: [],

                subject: '[IMAP message could not be parsed]',

                textBody: null,
                htmlBody: null,

                headers: $this->parseHeaders(
                    $rawHeader
                ),

                attachments: [],

                receivedAt: $this->safeReceivedAt(
                    $message
                ),

                inReplyToMessageId: null,
                references: [],

                metadata: [
                    'imap_uid' => $uid,

                    'imap_uidvalidity' => $uidValidity,

                    'imap_folder' => $configuration->folder,

                    'imap_message_number' => $this->safeInteger(
                        static fn (): mixed => $message->getMsgn()
                    ),

                    'imap_size' => $this->safeInteger(
                        static fn (): mixed => $message->getSize()
                    ),

                    'raw_message_omitted' => $rawMessageOmitted,

                    'raw_message_omission_reason' => $rawMessageOmissionReason,

                    'normalization_failed' => true,
                ],

                rawMessage: $rawMessage,

                rejectedAttachments: [],
            );

        return new FailedInboundMessageData(
            acknowledgementMessage: $acknowledgementMessage,

            errorCode: $errorCode,

            errorMessage: trim(
                $exception->getMessage()
            ) !== ''
                ? $exception->getMessage()
                : 'IMAP message normalization failed.',

            exceptionClass: $exception::class,

            retryable: $retryable,

            metadata: [
                'driver_context' => $context,

                'file' => $exception->getFile(),

                'line' => $exception->getLine(),
            ],
        );
    }

    /**
     * @return array<int, MailAddressData>
     */
    private function addresses(
        Attribute $attribute
    ): array {
        $addresses = [];

        foreach (
            $attribute->toArray() as $value
        ) {
            if ($value instanceof Address) {
                $email = trim(
                    $value->mail
                );

                if (
                    $email === ''
                    || filter_var(
                        $email,
                        FILTER_VALIDATE_EMAIL
                    ) === false
                ) {
                    continue;
                }

                $addresses[] =
                    new MailAddressData(
                        address: $email,

                        name: trim(
                            $value->personal
                        ) !== ''
                            ? trim(
                                $value->personal
                            )
                            : null,
                    );

                continue;
            }

            if (! is_scalar($value)) {
                continue;
            }

            $email = trim(
                (string) $value
            );

            if (
                $email === ''
                || filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                ) === false
            ) {
                continue;
            }

            $addresses[] =
                new MailAddressData(
                    address: $email,
                );
        }

        return $addresses;
    }

    /**
     * @return array{
     *     0: array<int, MailAttachmentData>,
     *     1: array<int, RejectedMailAttachmentData>
     * }
     */
    private function attachments(
        Message $message,
        ?string $htmlBody,
        ImapChannelConfigurationData $configuration,
    ): array {
        $accepted = [];
        $rejected = [];

        foreach (
            $message->getAttachments() as $attachment
        ) {
            if (
                ! $attachment
                    instanceof Attachment
            ) {
                continue;
            }

            $descriptor =
                $this->attachmentDescriptor(
                    attachment: $attachment,

                    htmlBody: $htmlBody,
                );

            try {
                $content =
                    $attachment->getContent();

                if (! is_string($content)) {
                    $content =
                        (string) $content;
                }
            } catch (Throwable $exception) {
                $rejected[] =
                    new RejectedMailAttachmentData(
                        fileName: $descriptor['file_name'],

                        mimeType: $descriptor['mime_type'],

                        reportedSize: $descriptor[
                        'reported_size'
                        ],

                        reasonCode: 'imap_attachment_read_failed',

                        reasonMessage: $exception->getMessage(),

                        externalId: $descriptor[
                        'external_id'
                        ],

                        contentId: $descriptor[
                        'content_id'
                        ],

                        inline: $descriptor['inline'],

                        metadata: [
                            'imap_part_number' => $descriptor[
                                'part_number'
                                ],

                            'imap_disposition' => $descriptor[
                                'disposition'
                                ],

                            'exception_class' => $exception::class,
                        ],
                    );

                continue;
            }

            $size = strlen($content);

            if (
                $size
                > $configuration
                    ->maxAttachmentBytes
            ) {
                $rejected[] =
                    new RejectedMailAttachmentData(
                        fileName: $descriptor['file_name'],

                        mimeType: $descriptor['mime_type'],

                        reportedSize: $size,

                        reasonCode: 'imap_attachment_too_large',

                        reasonMessage: sprintf(
                            'Attachment size %d bytes exceeds '
                            .'the configured limit of %d bytes.',
                            $size,
                            $configuration
                                ->maxAttachmentBytes,
                        ),

                        externalId: $descriptor[
                        'external_id'
                        ],

                        contentId: $descriptor[
                        'content_id'
                        ],

                        inline: $descriptor['inline'],

                        metadata: [
                            'imap_part_number' => $descriptor[
                                'part_number'
                                ],

                            'imap_disposition' => $descriptor[
                                'disposition'
                                ],

                            'attachment_size' => $size,

                            'attachment_limit' => $configuration
                                ->maxAttachmentBytes,

                            'imap_reported_size' => $descriptor[
                                'reported_size'
                                ],
                        ],
                    );

                unset($content);

                continue;
            }

            $accepted[] =
                new MailAttachmentData(
                    fileName: $descriptor['file_name'],

                    mimeType: $descriptor['mime_type'],

                    size: $size,

                    content: $content,

                    externalId: $descriptor[
                    'external_id'
                    ],

                    contentId: $descriptor['inline']
                        ? $descriptor[
                    'content_id'
                    ]
                        : null,

                    inline: $descriptor['inline'],

                    metadata: [
                        'imap_part_number' => $descriptor[
                            'part_number'
                            ],

                        'imap_disposition' => $descriptor[
                            'disposition'
                            ],

                        'imap_reported_size' => $descriptor[
                            'reported_size'
                            ],
                    ],
                );
        }

        return [
            $accepted,
            $rejected,
        ];
    }

    private function attachmentDescriptor(
        Attachment $attachment,
        ?string $htmlBody,
    ): array {
        $fileName =
            $this->attachmentName(
                $attachment
            );

        $mimeType =
            $this->attachmentMimeType(
                $attachment
            );

        $contentId =
            $this->safeContentId(
                $attachment
            );

        $disposition =
            $this->safeString(
                static fn (): mixed => $attachment
                    ->getDisposition()
            );

        $disposition =
            $disposition !== null
                ? strtolower($disposition)
                : null;

        $inline =
            $disposition === 'inline';

        if (
            ! $inline
            && $contentId !== null
            && $htmlBody !== null
            && stripos(
                $htmlBody,
                'cid:'.$contentId
            ) !== false
        ) {
            $inline = true;
        }

        $externalId =
            $this->safeString(
                static fn (): mixed => $attachment->getHash()
            );

        $partNumber =
            $this->safeString(
                static fn (): mixed => $attachment
                    ->getPartNumber()
            );

        if ($externalId === null) {
            $externalId =
                'part:'
                .(
                    $partNumber
                    ?? 'unknown'
                );
        }

        return [
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'content_id' => $contentId,
            'disposition' => $disposition,
            'inline' => $inline,
            'external_id' => $externalId,
            'part_number' => $partNumber,

            'reported_size' => $this->safeInteger(
                static fn (): mixed => $attachment->getSize()
            ),
        ];
    }

    private function attachmentName(
        Attachment $attachment
    ): string {
        $name =
            $this->safeString(
                static fn (): mixed => $attachment->getName()
            );

        if ($name === null) {
            $partNumber =
                $this->safeString(
                    static fn (): mixed => $attachment
                        ->getPartNumber()
                );

            $name =
                'attachment-'
                .(
                    $partNumber
                    ?? 'unknown'
                );
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

        $name = trim(
            (string) $name
        );

        return $name !== ''
            ? mb_substr(
                $name,
                0,
                255
            )
            : 'attachment.bin';
    }

    private function attachmentMimeType(
        Attachment $attachment
    ): string {
        $mimeType =
            $this->safeString(
                static fn (): mixed => $attachment
                    ->getContentType()
            );

        if ($mimeType === null) {
            $mimeType =
                $this->safeString(
                    static fn (): mixed => $attachment
                        ->getMimeType()
                );
        }

        return $mimeType
            ?? 'application/octet-stream';
    }

    private function safeContentId(
        Attachment $attachment
    ): ?string {
        $contentId =
            $this->safeString(
                static fn (): mixed => $attachment->getId()
            );

        if ($contentId === null) {
            return null;
        }

        $contentId = trim(
            $contentId,
            " \t\n\r\0\x0B<>"
        );

        return $contentId !== ''
            ? $contentId
            : null;
    }

    /**
     * @return array{0: ?string, 1: bool, 2: ?string}
     */
    private function rawMessage(
        Message $message,
        string $rawHeader,
        ImapChannelConfigurationData $configuration,
    ): array {
        if (! $configuration->storeRawMessage) {
            return [
                null,
                true,
                'storage_disabled',
            ];
        }

        try {
            $rawBody =
                $message->getRawBody();
        } catch (Throwable $exception) {
            return [
                null,
                true,
                'read_failed: '
                .$exception->getMessage(),
            ];
        }

        $rawMessage =
            rtrim(
                $rawHeader,
                "\r\n"
            )
            ."\r\n\r\n"
            .$rawBody;

        if (
            strlen($rawMessage)
            > $configuration
                ->maxRawMessageBytes
        ) {
            return [
                null,
                true,
                'size_limit_exceeded',
            ];
        }

        return [
            $rawMessage,
            false,
            null,
        ];
    }

    private function receivedAt(
        Message $message
    ): DateTimeImmutable {
        $value =
            $message->getDate()->first();

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

        return new DateTimeImmutable;
    }

    private function safeReceivedAt(
        Message $message
    ): DateTimeImmutable {
        try {
            return $this->receivedAt(
                $message
            );
        } catch (Throwable) {
            return new DateTimeImmutable;
        }
    }

    /**
     * @return array<int, string>
     */
    private function references(
        Attribute $attribute
    ): array {
        $references = [];

        foreach (
            $attribute->toArray() as $value
        ) {
            if (! is_scalar($value)) {
                continue;
            }

            $value = trim(
                (string) $value
            );

            if ($value === '') {
                continue;
            }

            preg_match_all(
                '/<([^>]+)>/',
                $value,
                $matches
            );

            if (
                ($matches[1] ?? [])
                !== []
            ) {
                foreach (
                    $matches[1] as $messageId
                ) {
                    $messageId = trim(
                        $messageId
                    );

                    if ($messageId !== '') {
                        $references[] =
                            $messageId;
                    }
                }

                continue;
            }

            foreach (
                preg_split(
                    '/\s+/',
                    $value
                ) ?: [] as $messageId
            ) {
                $messageId = trim(
                    $messageId,
                    " \t\n\r\0\x0B<>"
                );

                if ($messageId !== '') {
                    $references[] =
                        $messageId;
                }
            }
        }

        return array_values(
            array_unique(
                $references
            )
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
            ) ?: [] as $line
        ) {
            if (! str_contains($line, ':')) {
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
            $message
                ->getFlags()
                ->toArray() as $flag
        ) {
            if (is_scalar($flag)) {
                $flags[] =
                    (string) $flag;

                continue;
            }

            if (
                is_object($flag)
                && method_exists(
                    $flag,
                    '__toString'
                )
            ) {
                $flags[] =
                    (string) $flag;
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
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim(
            (string) $value
        );

        return $value !== ''
            ? $value
            : null;
    }

    private function normalizeMessageId(
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

    private function safeRawHeader(
        Message $message
    ): string {
        try {
            return (string) (
                $message
                    ->getHeader()
                    ->raw
                ?? ''
            );
        } catch (Throwable) {
            return '';
        }
    }

    private function safeString(
        callable $resolver
    ): ?string {
        try {
            $value = $resolver();
        } catch (Throwable) {
            return null;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim(
            (string) $value
        );

        return $value !== ''
            ? $value
            : null;
    }

    private function safeInteger(
        callable $resolver
    ): int {
        try {
            return max(
                0,
                (int) $resolver()
            );
        } catch (Throwable) {
            return 0;
        }
    }

    private function externalMessageId(
        string $folder,
        int $uidValidity,
        int $uid,
    ): string {
        return implode(':', [
            'imap',
            $folder,
            $uidValidity,
            $uid,
        ]);
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

<?php

namespace App\Services\Admin\Mail\ReplyParsing;

use App\Data\Admin\Mail\InboundEmailDecisionData;
use App\Enums\Admin\Mail\InboundEmailClassification;
use App\Models\Admin\Mail\EmailMessage;

class InboundEmailClassifier
{
    public function classify(
        EmailMessage $message
    ): InboundEmailDecisionData {
        if ($this->isSameMailboxSender($message)) {
            return $this->decision(
                shouldProcess: !$this->shouldIgnore(
                    'same_mailbox_sender'
                ),
                classification:
                InboundEmailClassification::Loop,
                reason: 'sender_matches_mailbox',
            );
        }

        if ($this->hasSimpleDeskOrigin($message)) {
            return $this->decision(
                shouldProcess: !$this->shouldIgnore(
                    'simpledesk_origin'
                ),
                classification:
                InboundEmailClassification::Loop,
                reason: 'simpledesk_origin_header',
            );
        }

        if ($this->isDeliveryStatusMessage($message)) {
            return $this->decision(
                shouldProcess: !$this->shouldIgnore(
                    'delivery_status'
                ),
                classification:
                InboundEmailClassification::DeliveryStatus,
                reason: 'delivery_status_notification',
            );
        }

        if ($this->isAutoReply($message)) {
            return $this->decision(
                shouldProcess: !$this->shouldIgnore(
                    'auto_replies'
                ),
                classification:
                InboundEmailClassification::AutoReply,
                reason: 'automatic_response',
            );
        }

        if ($this->isBulkMessage($message)) {
            return $this->decision(
                shouldProcess: !$this->shouldIgnore(
                    'bulk'
                ),
                classification:
                InboundEmailClassification::Bulk,
                reason: 'bulk_or_mailing_list_message',
            );
        }

        return $this->decision(
            shouldProcess: true,
            classification:
            InboundEmailClassification::Human,
            reason: 'human_message',
        );
    }

    private function isSameMailboxSender(
        EmailMessage $message
    ): bool {
        $sender = $this->normalizeAddress(
            $message->sender_address
        );

        $mailboxAddress = $this->normalizeAddress(
            $message
                ->mailbox
                ?->email_address
        );

        return $sender !== null
            && $mailboxAddress !== null
            && $sender === $mailboxAddress;
    }

    private function hasSimpleDeskOrigin(
        EmailMessage $message
    ): bool {
        return $this->headerValues(
                $message,
                'x-simpledesk-origin'
            ) !== [];
    }

    private function isDeliveryStatusMessage(
        EmailMessage $message
    ): bool {
        $contentType = strtolower(
            $this->headerText(
                $message,
                'content-type'
            )
        );

        if (
            str_contains(
                $contentType,
                'multipart/report'
            )
            || str_contains(
                $contentType,
                'message/delivery-status'
            )
            || str_contains(
                $contentType,
                'report-type=delivery-status'
            )
        ) {
            return true;
        }

        $returnPath = strtolower(
            trim(
                $this->headerText(
                    $message,
                    'return-path'
                )
            )
        );

        if (
            $returnPath === '<>'
            || $returnPath === ''
            && $this->isMailerDaemonSender(
                $message
            )
        ) {
            return true;
        }

        if ($this->isMailerDaemonSender($message)) {
            return true;
        }

        $subject = mb_strtolower(
            trim(
                (string) $message->subject
            )
        );

        return preg_match(
                '/(?:'
                . 'delivery status notification'
                . '|delivery failure'
                . '|delivery has failed'
                . '|undeliverable'
                . '|returned mail'
                . '|failure notice'
                . '|mail delivery failed'
                . '|не доставлено'
                . '|ошибка доставки'
                . '|сбой доставки'
                . ')/iu',
                $subject
            ) === 1;
    }

    private function isMailerDaemonSender(
        EmailMessage $message
    ): bool {
        $sender = $this->normalizeAddress(
            $message->sender_address
        );

        if ($sender === null) {
            return false;
        }

        $localPart = strstr(
            $sender,
            '@',
            true
        );

        if ($localPart === false) {
            $localPart = $sender;
        }

        return in_array(
            strtolower($localPart),
            [
                'mailer-daemon',
                'mail-daemon',
                'postmaster',
            ],
            true
        );
    }

    private function isAutoReply(
        EmailMessage $message
    ): bool {
        $autoSubmitted = strtolower(
            trim(
                $this->headerText(
                    $message,
                    'auto-submitted'
                )
            )
        );

        if (
            $autoSubmitted !== ''
            && $autoSubmitted !== 'no'
        ) {
            return true;
        }

        foreach (
            [
                'x-autoreply',
                'x-autorespond',
                'x-auto-reply',
                'x-autoresponse',
            ]
            as $header
        ) {
            if (
                $this->headerValues(
                    $message,
                    $header
                ) !== []
            ) {
                return true;
            }
        }

        $subject = mb_strtolower(
            trim(
                (string) $message->subject
            )
        );

        return preg_match(
                '/^(?:'
                . 'automatic reply'
                . '|auto reply'
                . '|autoreply'
                . '|out of office'
                . '|away from the office'
                . '|автоматический ответ'
                . '|автоответ'
                . '|вне офиса'
                . '|нет на рабочем месте'
                . ')\s*[:\-]?/iu',
                $subject
            ) === 1;
    }

    private function isBulkMessage(
        EmailMessage $message
    ): bool {
        if (
            $this->headerValues(
                $message,
                'list-id'
            ) !== []
        ) {
            return true;
        }

        $precedence = strtolower(
            trim(
                $this->headerText(
                    $message,
                    'precedence'
                )
            )
        );

        return in_array(
            $precedence,
            [
                'bulk',
                'list',
                'junk',
            ],
            true
        );
    }

    private function shouldIgnore(
        string $type
    ): bool {
        return (bool) config(
            "simpledesk-mail-reply-parsing.ignore.{$type}",
            true
        );
    }

    private function decision(
        bool $shouldProcess,
        InboundEmailClassification $classification,
        string $reason,
    ): InboundEmailDecisionData {
        return new InboundEmailDecisionData(
            shouldProcess: $shouldProcess,
            classification: $classification,
            reason: $reason,
        );
    }

    /**
     * @return array<int, string>
     */
    private function headerValues(
        EmailMessage $message,
        string $headerName,
    ): array {
        $headers = $message->headers;

        if (is_string($headers)) {
            $decoded = json_decode(
                $headers,
                true
            );

            $headers = is_array($decoded)
                ? $decoded
                : [];
        }

        if (!is_array($headers)) {
            return [];
        }

        $headerName = strtolower(
            trim($headerName)
        );

        foreach ($headers as $name => $values) {
            if (
                strtolower(
                    trim((string) $name)
                ) !== $headerName
            ) {
                continue;
            }

            if (!is_array($values)) {
                $values = [$values];
            }

            return array_values(
                array_filter(
                    array_map(
                        static fn (
                            mixed $value
                        ): string => trim(
                            (string) $value
                        ),
                        $values,
                    ),
                    static fn (
                        string $value
                    ): bool => $value !== '',
                )
            );
        }

        return [];
    }

    private function headerText(
        EmailMessage $message,
        string $headerName,
    ): string {
        return implode(
            ' ',
            $this->headerValues(
                $message,
                $headerName
            )
        );
    }

    private function normalizeAddress(
        ?string $address
    ): ?string {
        if ($address === null) {
            return null;
        }

        $address = strtolower(
            trim($address)
        );

        return filter_var(
            $address,
            FILTER_VALIDATE_EMAIL
        ) !== false
            ? $address
            : null;
    }
}
